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
     * @param  \BlueMedia\BluePayment\Api\Data\ShippingMethodInterface[]  $result
     * @param $cartId
     *
     * @return \BlueMedia\BluePayment\Api\Data\ShippingMethodInterface[]
     */
    public function afterGetShippingMethods(QuoteManagement $subject, array $result, $cartId): array
    {
        foreach ($result as $shippingMethod) {
            // Wykonujemy mapowanie, tak aby po stronie APC obsłużyć paczkomat

            if ($shippingMethod->getCarrierCode() === 'paczkomaty' && $shippingMethod->getMethodCode() === 'paczkomaty') {
                $shippingMethod->setCarrierCode('inpostlocker');
                $shippingMethod->setMethodCode('standard');
            }
        }

        return $result;
    }

    /**
     * @param  QuoteManagement  $subject
     * @param  callable  $proceed
     * @param  int  $cartId
     * @param  string  $carrierCode
     * @param  string  $methodCode
     * @param  ShippingMethodAdditionalInterface|null  $additional
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function aroundSetShippingMethod(
        QuoteManagement $subject,
        callable $proceed,
        $cartId,
        $carrierCode,
        $methodCode,
        ShippingMethodAdditionalInterface $additional = null
    ): bool {
        // Wykonujemy odwrotne mapowanie
        if ($carrierCode === 'inpostlocker' && $methodCode === 'standard') {
            $carrierCode = 'paczkomaty';
            $methodCode = 'paczkomaty';
        }

        $result = $proceed($cartId, $carrierCode, $methodCode, $additional);

        // Wykonujemy dodatkową logikę związaną z nasza niestandardową metodą paczkomatów.
        if ($carrierCode === 'paczkomaty' && $methodCode == 'paczkomaty') {
            $cart = $this->cartRepository->get($cartId);
            $cartExtensionAttributes = $cart->getExtensionAttributes();
            if (!$cartExtensionAttributes) {
                $cartExtensionAttributes = $this->cartExtensionFactory->create();
            }

            // Poniżej jest tylko przykład - należy dostosować to do faktycznego stanu aplikacji.
            $cartExtensionAttributes->setPaczkomat($additional->getLockerId());

            $cart->setExtensionAttributes($cartExtensionAttributes);
        }

        $this->cartRepository->save($cart);

        return $result;
    }
}
