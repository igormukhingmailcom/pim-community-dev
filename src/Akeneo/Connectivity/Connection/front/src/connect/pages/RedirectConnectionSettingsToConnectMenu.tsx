import {ClientErrorIllustration, Breadcrumb} from 'akeneo-design-system';
import React, {FC} from 'react';
import {Translate, useTranslate} from '../../shared/translate';
import {HelperLink, PageHeader} from '../../common';
import {UserButtons} from '../../shared/user';
import styled from 'styled-components';
import {useRouter} from '../../shared/router/use-router';

const PageContent = styled.div`
    text-align: center;
    margin-top: 100px;
    & > * {
        margin-bottom: 20px;
    }
`;

const Caption = styled.p`
    font-size: 23px;
    line-height: 1.2em;
`;

const Heading = styled.h1`
    color: ${({theme}) => theme.color.grey140};
    font-size: 28px;
    font-weight: normal;
    margin: 0;
    margin-bottom: 21px;
    line-height: 1.2em;
`;

export const RedirectConnectionSettingsToConnectMenu: FC = () => {
    const translate = useTranslate();
    const generateUrl = useRouter();

    const breadcrumb = (
        <Breadcrumb>
            <Breadcrumb.Step href={`#${generateUrl('oro_config_configuration_system')}`}>
                {translate('pim_menu.tab.system')}
            </Breadcrumb.Step>
            <Breadcrumb.Step>{translate('pim_menu.item.connection_settings')}</Breadcrumb.Step>
        </Breadcrumb>
    );

    return (
        <>
            <PageHeader breadcrumb={breadcrumb} userButtons={<UserButtons />}>
                {translate('pim_menu.item.connection_settings')}
            </PageHeader>

            <PageContent>
                <ClientErrorIllustration width={500} height={250} />

                <Heading>{translate('akeneo_connectivity.connection.connect.settings_redirect.title')}</Heading>

                <Caption>
                    <HelperLink href={`#${generateUrl('akeneo_connectivity_connection_settings_index')}`}>
                        <Translate id='akeneo_connectivity.connection.connect.redirect.link' />
                    </HelperLink>
                </Caption>
            </PageContent>
        </>
    );
};
