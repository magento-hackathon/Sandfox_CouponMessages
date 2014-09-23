<?php

class Sandfox_CouponMessages_Model_Observer
{
    protected $attributes = array(
        'base_subtotal' => 'cart subtotal',
        'total_qty'     => 'total quantity',
        'weight'        => 'total items weight'
    );

    protected $operators = array(
        '=='    => 'must be equal to',
        '<='    => 'must be less than or equal to',
        '>='    => 'must be greater than or equal to',
        '>'     => 'must be greater than',
        '<'     => 'must be less than',
        '!='    => 'can\'t be equal to'
    );

    /**
     * If coupon code was sent and it is not valid add an error message(s) with the reason what is wrong with the coupon
     *
     * @param Varien_Event_Observer $observer
     */
    public function salesQuoteCollectTotalsAfter(Varien_Event_Observer $observer)
    {
        if (Mage::app()->getRequest()->getParam('remove') != 1) {

            $couponCode = (string) Mage::app()->getRequest()->getParam('coupon_code');
            $codeLength = strlen($couponCode);
            $isCodeLengthValid = $codeLength && $codeLength <= Mage_Checkout_Helper_Cart::COUPON_CODE_MAX_LENGTH;

            /** @var Mage_Sales_Model_Quote $quote */
            $quote = $observer->getData('quote');

            if ($isCodeLengthValid && $couponCode != $quote->getCouponCode()) {

                /** @var Mage_SalesRule_Model_Coupon $coupon */
                $coupon = Mage::getModel('salesrule/coupon')->load($couponCode, 'code');

                if ($coupon->getId()) {
                    $rule = Mage::getModel('salesrule/rule')->load($coupon->getRuleId());

                    if ($rule->getId()) {
                        /** @var Sandfox_CouponMessages_Helper_Data $helper */
                        $helper = Mage::helper('sandfox_couponmessages');

                        $data = unserialize($rule->getData('conditions_serialized'));

                        foreach ($data['conditions'] as $condition) {
                            foreach ($this->attributes as $attribute_code => $attribute_label) {
                                if ($condition['attribute'] == $attribute_code && $condition['value']) {
                                    foreach ($this->operators as $operator => $operator_label) {
                                        if ($condition['operator'] == $operator) {
                                            Mage::getSingleton('checkout/session')->addError(
                                                $helper->__(
                                                    'Your ' . $attribute_label . ' ' . $operator_label . ' "%s".',
                                                    $condition['value']
                                                )
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}