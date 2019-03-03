<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerGraphQl\Model\Resolver;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\CustomerGraphQl\Model\Customer\Address\GetCustomerAddressForUser;
use Magento\CustomerGraphQl\Model\Customer\CheckCustomerAccount;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

/**
 * Customers address delete, used for GraphQL request processing.
 */
class DeleteCustomerAddress implements ResolverInterface
{
    /**
     * @var CheckCustomerAccount
     */
    private $checkCustomerAccount;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var GetCustomerAddressForUser
     */
    private $getCustomerAddressForUser;

    /**
     * @param CheckCustomerAccount $checkCustomerAccount
     * @param AddressRepositoryInterface $addressRepository
     * @param GetCustomerAddressForUser $getCustomerAddressForUser
     */
    public function __construct(
        CheckCustomerAccount $checkCustomerAccount,
        AddressRepositoryInterface $addressRepository,
        GetCustomerAddressForUser $getCustomerAddressForUser
    ) {
        $this->checkCustomerAccount = $checkCustomerAccount;
        $this->addressRepository = $addressRepository;
        $this->getCustomerAddressForUser = $getCustomerAddressForUser;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $currentUserId = $context->getUserId();
        $currentUserType = $context->getUserType();

        $this->checkCustomerAccount->execute($currentUserId, $currentUserType);

        return $this->deleteCustomerAddress((int)$currentUserId, (int)$args['id']);
    }

    /**
     * Delete customer address
     *
     * @param int $customerId
     * @param int $addressId
     * @return bool
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
     */
    private function deleteCustomerAddress($customerId, $addressId)
    {
        $address = $this->getCustomerAddressForUser->execute($addressId, $customerId);
        if ($address->isDefaultBilling()) {
            throw new GraphQlAuthorizationException(
                __('Customer Address %1 is set as default billing address and can not be deleted', [$addressId])
            );
        }
        if ($address->isDefaultShipping()) {
            throw new GraphQlAuthorizationException(
                __('Customer Address %1 is set as default shipping address and can not be deleted', [$addressId])
            );
        }
        return $this->addressRepository->delete($address);
    }
}
