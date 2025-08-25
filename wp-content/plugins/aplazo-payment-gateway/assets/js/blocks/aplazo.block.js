
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting( 'dummy_data', {} );
const paymentMethodName = 'aplazo';

const defaultLabel = 'Aplazo - Paga en plazos sin tarjeta de crédito';
const label = decodeEntities( settings.title ) || defaultLabel;

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = ( props ) => {
    const { PaymentMethodLabel } = props.components;
    return <PaymentMethodLabel text={ label } />;
};
const Content = () => {
    return decodeEntities( settings.description || '' );
};

const options = {
    name: paymentMethodName,
    label: <Label />,
    content: <Content />,
    edit: <Content />,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
};

registerPaymentMethod(options);