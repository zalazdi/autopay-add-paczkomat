<?php

namespace BlueMedia\AutopayAddPaczkomat\Plugin;

use BlueMedia\BluePayment\Api\Data\ShippingMethodAdditionalInterface;
use BlueMedia\BluePayment\Api\QuoteManagementInterface;
use BlueMedia\BluePayment\Model\QuoteManagement;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;

class SetPaczkomatInShippingMethod
{
    /** @var CartRepositoryInterface */
    private $cartRepository;

    /** @var CartExtensionFactory  */
    private $cartExtensionFactory;

    public function __construct(CartRepositoryInterface $cartRepository, CartExtensionFactory $cartExtensionFactory)
    {
        $this->cartRepository = $cartRepository;
        $this->cartExtensionFactory = $cartExtensionFactory;
    }

    /**
     * @param  QuoteManagement  $subject
     * @param  bool  $result
     * @param  int  $cartId
     * @param  string  $carrierCode
     * @param  string  $methodCode
     * @param  ShippingMethodAdditionalInterface|null  $additional
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function afterSetShippingMethod(
        QuoteManagement $subject,
        bool $result,
        $cartId,
        $carrierCode,
        $methodCode,
        ShippingMethodAdditionalInterface $additional = null
    ): bool {
        $cart = $this->cartRepository->get($cartId);

        if ($carrierCode === 'paczkomaty' && $methodCode == 'paczkomaty') {
            $cartExtensionAttributes = $cart->getExtensionAttributes();
            if (!$cartExtensionAttributes) {
                $cartExtensionAttributes = $this->cartExtensionFactory->create();
            }

            // Poniżej jest tylko przykład - należy dostosować to do faktycznego stanu aplikacji.
            $cartExtensionAttributes->setPaczkomat($additional->getLockerId());

            $cart->setExtensionAttributes($cartExtensionAttributes);
        }

        // TODO: Implement plugin method.
        return $result;
    }
}
