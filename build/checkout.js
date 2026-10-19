const settingsN1co = window.wc.wcSettings.getSetting('n1co_gateway_data', {});
const labelN1co = window.wp.htmlEntities.decodeEntities(settingsN1co.title) || window.wp.i18n.__('N1co Gateway', 'n1co_gateway');
const ContentN1co = () => {
    return window.wp.htmlEntities.decodeEntities(settingsN1co.description || '');
};
const Block_Gateway_N1co = {
    name: 'n1co_gateway',
    label: labelN1co,
    content: Object(window.wp.element.createElement)(ContentN1co, null),
    edit: Object(window.wp.element.createElement)(ContentN1co, null),
    canMakePayment: () => true,
    ariaLabel: labelN1co,
    placeOrderButtonLabel: "Proceder por n1co",
    supports: {
        features: settingsN1co.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway_N1co);