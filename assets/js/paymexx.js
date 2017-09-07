
function payWithPaymexx(){

    var data = {
        mid:wc_paymexx_params.merchant_id,
        email:wc_paymexx_params.email,
        shopname:wc_paymexx_params.shop_name,
        env:wc_paymexx_params.env,
        amount:wc_paymexx_params.amount,
        reference:wc_paymexx_params.txn_ref,
        firstname:wc_paymexx_params.firstname,
        lastname:wc_paymexx_params.lastname,
        address:wc_paymexx_params.address,
        currency:wc_paymexx_params.currency,
        redirect_url:wc_paymexx_params.return_url,
    };

    Paymexx.init(data);
}









//
//
// jQuery( function( $ ) {
//
//     var simplepay_submit = false;
//
//     /* Pay Page Form */
//     jQuery( '#simplepay-payment-button' ).click( function() {
//         return simplePayFormHandler();
//     });
//
//     jQuery( '#simplepay_form form#order_review' ).submit( function() {
//         return simplePayFormHandler();
//     });
//
//     function simplePayFormHandler() {
//
//         if ( simplepay_submit ) {
//             simplepay_submit = false;
//             return true;
//         }
//
//         var $form            = $( 'form#payment-form, form#order_review' ),
//             wc_simplepay_token  = $form.find( 'input.wc_simplepay_token' );
//
//         wc_simplepay_token.val( '' );
//
//         var simplepay_callback = function( token ) {
//
//             $form.append( '<input type="hidden" class="wc_simplepay_token" name="wc_simplepay_token" value="' + token + '"/>' );
//             $form.append( '<input type="hidden" class="wc_simplepay_token" name="wc_simplepay_order_id" value="' + wc_simplepay_params.order_id + '"/>' );
//             simplepay_submit = true;
//
//             $form.submit();
//
//             $( this.el ).block({
//                 message: null,
//                 timeout: 4000,
//                 overlayCSS: {
//                     background: '#fff',
//                     opacity: 0.6
//                 }
//             });
//
//         };
//
//         var wcSimplepayHandler = SimplePay.configure({
//             token: simplepay_callback,
//             key: wc_simplepay_params.key,
//             image: wc_simplepay_params.logo,
//             onClose: function() {
//                 $( this.el ).unblock();
//             }
//         });
//
//         wcSimplepayHandler.open( SimplePay.CHECKOUT,
//             {
//                 email: wc_paymexx_params.email,
//                 address: wc_paymexx_params.address,
//                 city: wc_paymexx_params.city,
//                 country: wc_paymexx_params.country,
//                 amount: wc_paymexx_params.amount,
//                 description: wc_paymexx_params.description,
//                 currency: wc_paymexx_params.currency
//             } );
//
//         return false;
//
//     }
//
// } );


