import React, {ReactNode} from 'react';
import styled from 'styled-components';

export const CredentialList = styled.div`
    display: grid;
    grid-template-columns: repeat(3, auto);
`;

export const Credential = ({
    label,
    children: value,
    actions,
    helper,
}: {
    label: ReactNode;
    children: ReactNode;
    actions?: ReactNode;
    helper?: ReactNode;
}) => (
    <>
        <Label withHelper={!!helper}>{label}</Label>
        <Value withHelper={!!helper}>{value}</Value>
        <Actions withHelper={!!helper}>{actions}</Actions>
        {helper && <Helper>{helper}</Helper>}
    </>
);

const Column = styled.div<{withHelper: boolean}>`
    border-bottom: ${({withHelper = false, theme}) => (withHelper ? 'none' : `1px solid ${theme.color.grey80}`)};
    height: 54px;
    line-height: 54px;
    padding: 0 10px;
`;

const Label = styled(Column)`
    color: ${({theme}) => theme.color.purple100};
    padding-left: 20px;
`;

const Value = styled(Column)`
    color: ${({theme}) => theme.color.grey140};
    overflow: hidden;
    text-overflow: ellipsis;
`;

const Actions = styled(Column)`
    align-items: center;
    display: flex;
    justify-content: flex-end;
    padding-right: 20px;

    > * {
        margin: 0 5px;

        :first-child {
            margin-left: 0;
        }
        :last-child {
            margin-right: 0;
        }
    }
`;

const Helper = styled.div`
    border-bottom: 1px solid ${({theme}) => theme.color.grey80};
    grid-column: 1 / 4;
    padding: 0 20px 20px 20px;
`;
