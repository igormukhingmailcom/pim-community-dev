<?php

declare(strict_types=1);

namespace Akeneo\Test\IntegrationTestsBundle\Launcher;

use Akeneo\Tool\Bundle\BatchBundle\Command\BatchCommand;
use Akeneo\Tool\Bundle\BatchQueueBundle\Command\JobQueueConsumerCommand;
use Akeneo\Tool\Bundle\BatchQueueBundle\Queue\JobExecutionMessageRepository;
use Akeneo\Tool\Component\Batch\Job\BatchStatus;
use Akeneo\Tool\Component\Batch\Model\JobExecution;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Webmozart\Assert\Assert;

/**
 * Launch jobs for the integration tests.
 *
 * @author    Alexandre Hocquard <alexandre.hocquard@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class JobLauncher
{
    private const MESSENGER_COMMAND_NAME = 'messenger:consume';
    private const MESSENGER_RECEIVERS = ['ui_job', 'import_export_job', 'data_maintenance_job'];

    const EXPORT_DIRECTORY = 'pim-integration-tests-export';

    const IMPORT_DIRECTORY = 'pim-integration-tests-import';

    private KernelInterface $kernel;
    private Connection $dbConnection;
    private JobExecutionMessageRepository $jobExecutionMessageRepository;
    private TransportInterface $jobQueueTransport;

    public function __construct(
        KernelInterface $kernel,
        Connection $dbConnection,
        JobExecutionMessageRepository $jobExecutionMessageRepository,
        TransportInterface $jobQueueTransport
    ) {
        $this->kernel = $kernel;
        $this->dbConnection = $dbConnection;
        $this->jobExecutionMessageRepository = $jobExecutionMessageRepository;
        $this->jobQueueTransport = $jobQueueTransport;
    }

    /**
     * @param string      $jobCode
     * @param string|null $username
     * @param array       $config
     *
     * @throws \Exception
     *
     * @return string
     */
    public function launchExport(string $jobCode, string $username = null, array $config = [], string $format = 'csv') : string
    {
        Assert::stringNotEmpty($format);
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR. self::EXPORT_DIRECTORY . DIRECTORY_SEPARATOR . 'export.' . $format;
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $config['filePath'] = $filePath;

        $arrayInput = [
            'command'  => 'akeneo:batch:job',
            'code'     => $jobCode,
            '--config' => json_encode($config),
            '--no-log' => true,
            '-v'       => true
        ];

        if (null !== $username) {
            $arrayInput['--username'] = $username;
        }

        $input = new ArrayInput($arrayInput);

        $output = new BufferedOutput();
        $exitCode = $application->run($input, $output);

        if (BatchCommand::EXIT_SUCCESS_CODE !== $exitCode) {
            throw new \Exception(sprintf('Export failed, "%s".', $output->fetch()));
        }

        if (!is_readable($filePath)) {
            throw new \Exception(sprintf('Exported file "%s" is not readable for the job "%s".', $filePath, $jobCode));
        }

        $content = file_get_contents($filePath);
        unlink($filePath);

        return $content;
    }

    /**
     * @param string      $jobCode
     * @param string|null $username
     * @param array       $config
     *
     * @throws \Exception
     *
     * @return string
     */
    public function launchAuthenticatedExport(string $jobCode, string $username = null, array $config = []) : string
    {
        $config['is_user_authenticated'] = true;

        return self::launchExport($jobCode, $username, $config);
    }

    /**
     * Launch an export in a subprocess because it's not possible to launch two exports in the same process.
     * The cause is that some services are stateful, such as the JSONFileBuffer that is not flushed after an export.
     *
     * TODO: fix  stateful services
     *
     * @param string      $jobCode
     * @param string|null $username
     * @param array       $config
     *
     * @throws \Exception
     *
     * @return string
     */
    public function launchSubProcessExport(string $jobCode, string $username = null, array $config = []) : string
    {
        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR. self::EXPORT_DIRECTORY . DIRECTORY_SEPARATOR . 'export_.csv';
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $config['filePath'] = $filePath;

        $pathFinder = new PhpExecutableFinder();
        $command = sprintf(
            '%s %s/console %s --env=%s --config=\'%s\' -v %s',
            $pathFinder->find(),
             sprintf('%s/../bin', $this->kernel->getRootDir()),
            'akeneo:batch:job',
            $this->kernel->getEnvironment(),
            json_encode($config, JSON_HEX_APOS),
            $jobCode
        );

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception(sprintf('Export failed, "%s".', $process->getOutput() . PHP_EOL . $process->getErrorOutput()));
        }

        if (!is_readable($filePath)) {
            throw new \Exception(sprintf('Exported file "%s" is not readable for the job "%s".', $filePath, $jobCode));
        }

        $content = file_get_contents($filePath);
        unlink($filePath);

        return $content;
    }

    /**
     * @param string      $jobCode
     * @param string      $content
     * @param string|null $username
     * @param array       $fixturePaths
     * @param array       $config
     *
     * @throws \Exception
     */
    public function launchImport(
        string $jobCode,
        string $content,
        string $username = null,
        array $fixturePaths = [],
        array $config = [],
        string $format = 'csv'
    ) : void {
        Assert::stringNotEmpty($format);
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $importDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::IMPORT_DIRECTORY;
        $fixturesDirectoryPath = $importDirectoryPath . DIRECTORY_SEPARATOR . 'fixtures';
        $filePath = $importDirectoryPath . DIRECTORY_SEPARATOR . 'import.' . $format;

        $fs = new Filesystem();
        $fs->remove($importDirectoryPath);
        $fs->remove($fixturesDirectoryPath);
        $fs->mkdir($importDirectoryPath);
        $fs->mkdir($fixturesDirectoryPath);

        file_put_contents($filePath, $content);

        foreach ($fixturePaths as $fixturePath) {
            $fixturesPath = $fixturesDirectoryPath . DIRECTORY_SEPARATOR . basename($fixturePath);
            $fs->copy($fixturePath, $fixturesPath, true);
        }

        $config['filePath'] = $filePath;

        $arrayInput = [
            'command'  => 'akeneo:batch:job',
            'code'     => $jobCode,
            '--config' => json_encode($config),
            '--no-log' => true,
            '-v'       => true
        ];

        if (null !== $username) {
            $arrayInput['--username'] = $username;
        }

        $input = new ArrayInput($arrayInput);

        $output = new BufferedOutput();
        $exitCode = $application->run($input, $output);

        if (BatchCommand::EXIT_SUCCESS_CODE !== $exitCode) {
            throw new \Exception(sprintf('Export failed, "%s".', $output->fetch()));
        }
    }

    /**
     * @param string      $jobCode
     * @param string      $content
     * @param string|null $username
     * @param array       $fixturePaths
     * @param array       $config
     */
    public function launchAuthenticatedImport(
        string $jobCode,
        string $content,
        string $username = null,
        array $fixturePaths = [],
        array $config = []
    ) :void {
        $config['is_user_authenticated'] = true;

        self::launchImport($jobCode, $content, $username, $fixturePaths, $config);
    }

    /**
     * Wait until a job has been finished.
     *
     * @param JobExecution $jobExecution
     *
     * @throws \RuntimeException
     */
    public function waitCompleteJobExecution(JobExecution $jobExecution): void
    {
        $timeout = 0;
        $isCompleted = false;

        $stmt = $this->dbConnection->prepare('SELECT status from akeneo_batch_job_execution where id = :id');

        while (!$isCompleted) {
            if ($timeout > 30) {
                throw new \RuntimeException(sprintf('Timeout: job execution "%s" is not complete.', $jobExecution->getId()));
            }
            $stmt->bindValue('id', $jobExecution->getId());
            $stmt->execute();
            $result = $stmt->fetch();

            $isCompleted = isset($result['status']) && BatchStatus::COMPLETED === (int) $result['status'];

            $timeout++;

            sleep(1);
        }
    }

    /**
     * Indicates whether the queue has a job still not consumed in the queue.
     *
     * @return bool
     */
    public function hasJobInQueue(): bool
    {
        $jobExecutionMessage = $this->jobExecutionMessageRepository->getAvailableJobExecutionMessage();

        return null !== $jobExecutionMessage;
    }

    /**
     * Launch the daemon command to consume and launch one job execution.
     *
     * @param array $options
     *
     * @return BufferedOutput
     */
    public function launchConsumerOnce(array $options = []): BufferedOutput
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $arrayInput = array_merge(
            $options,
            [
                'command'  => JobQueueConsumerCommand::COMMAND_NAME,
                '--run-once' => true,
            ]
        );

        $input = new ArrayInput($arrayInput);
        $output = new BufferedOutput();
        $application->run($input, $output);

        return $output;
    }

    /**
     * Launch the daemon command to consume and launch one job execution.
     * @TODO CPM-156: replace the launchConsumerOnce method by this one
     */
    public function launchConsumerOnceUsingMessenger(array $options = []): OutputInterface
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $arrayInput = array_merge(
            $options,
            [
                'command'  => static::MESSENGER_COMMAND_NAME,
                'receivers' => static::MESSENGER_RECEIVERS,
                '--limit' => 1,
            ]
        );

        $input = new ArrayInput($arrayInput);
        $output = new BufferedOutput();
        $application->run($input, $output);

        return $output;
    }

    /**
     * Launch the daemon command to consume and launch one job execution, in a detached process in background.
     * It uses exec to not wrap the process in a subshell, in order to get the correct pid.
     *
     * @see https://github.com/symfony/symfony/issues/5759
     *
     * @return Process
     */
    public function launchConsumerOnceInBackground(): Process
    {
        $command = sprintf(
            'exec %s/console %s --env=%s --run-once',
            sprintf('%s/../bin', $this->kernel->getRootDir()),
            JobQueueConsumerCommand::COMMAND_NAME,
            $this->kernel->getEnvironment()
        );

        $process = new Process($command);
        $process->start();

        return $process;
    }

    /**
     * @TODO CPM-156: replace the launchConsumerOnceInBackground method by this one
     *
     * Launch the daemon command to consume and launch one job execution, in a detached process in background.
     * It uses exec to not wrap the process in a subshell, in order to get the correct pid.
     *
     * @see https://github.com/symfony/symfony/issues/5759
     */
    public function launchConsumerOnceInBackgroundUsingMessenger(int $timeLimitInSeconds = null): Process
    {
        $command = sprintf(
            'exec %s/console %s %s --env=%s --limit=1 --verbose %s',
            sprintf('%s/../bin', $this->kernel->getContainer()->getParameter('kernel.root_dir')),
            static::MESSENGER_COMMAND_NAME,
            implode(' ', static::MESSENGER_RECEIVERS),
            $this->kernel->getEnvironment(),
            $timeLimitInSeconds === null ? '' : sprintf('--time-limit=%d', $timeLimitInSeconds)
        );

        $process = new Process($command);
        $process->start();

        return $process;
    }

    /**
     * Launch an import in a subprocess.
     *
     * @param string $jobCode
     * @param string $content
     * @param string $username
     * @param array  $fixturePaths
     * @param array  $config
     *
     * @throws \Exception
     */
    public function launchSubProcessImport(
        string $jobCode,
        string $content,
        ?string $username = null,
        array $fixturePaths = [],
        array $config = []
    ): void {
        $importDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::IMPORT_DIRECTORY;
        $fixturesDirectoryPath = $importDirectoryPath . DIRECTORY_SEPARATOR . 'fixtures';
        $filePath = $importDirectoryPath . DIRECTORY_SEPARATOR . 'import.csv';

        $fs = new Filesystem();
        $fs->remove($importDirectoryPath);
        $fs->remove($fixturesDirectoryPath);
        $fs->mkdir($importDirectoryPath);
        $fs->mkdir($fixturesDirectoryPath);

        foreach ($fixturePaths as $fixturePath) {
            $fixturesPath = $fixturesDirectoryPath . DIRECTORY_SEPARATOR . basename($fixturePath);
            $fs->copy($fixturePath, $fixturesPath, true);
        }

        file_put_contents($filePath, $content);

        $config['filePath'] =  $filePath;

        $username = null !== $username ? sprintf('--username=%s', $username) : '';

        $pathFinder = new PhpExecutableFinder();
        $command = sprintf(
            '%s %s/console %s --env=%s --config=\'%s\' -v %s %s',
            $pathFinder->find(),
            sprintf('%s/../bin', $this->kernel->getRootDir()),
            'akeneo:batch:job',
            $this->kernel->getEnvironment(),
            json_encode($config, JSON_HEX_APOS),
            $jobCode,
            $username
        );

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful() && !BatchCommand::EXIT_WARNING_CODE === $process->getExitCode()) {
            throw new \Exception(sprintf('Import failed, "%s".', $process->getOutput() . PHP_EOL . $process->getErrorOutput()));
        }
    }
    /**
     * Launch an import in a subprocess.
     *
     * @param string $jobCode
     * @param string $content
     * @param string $username
     * @param array  $fixturePaths
     * @param array  $config
     *
     * @throws \Exception
     */
    public function launchAuthenticatedSubProcessImport(
        string $jobCode,
        string $content,
        string $username,
        array $fixturePaths = [],
        array $config = []
    ): void {
        $config['is_user_authenticated'] = true;

        self::launchSubProcessImport($jobCode, $content, $username, $fixturePaths, $config);
    }

    public function flushMessengerJobQueue(): void
    {
        do {
            $enveloppes = $this->jobQueueTransport->get();
            $count = is_array($enveloppes) ? count($enveloppes) : iterator_count($enveloppes);
        } while(0 < $count);
    }
}
