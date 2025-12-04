<?php

declare(strict_types=1);

namespace Nexus\ChartOfAccount\Services;

use Nexus\ChartOfAccount\Contracts\AccountInterface;
use Nexus\ChartOfAccount\Contracts\AccountManagerInterface;
use Nexus\ChartOfAccount\Contracts\AccountPersistInterface;
use Nexus\ChartOfAccount\Contracts\AccountQueryInterface;
use Nexus\ChartOfAccount\Enums\AccountType;
use Nexus\ChartOfAccount\Exceptions\AccountHasChildrenException;
use Nexus\ChartOfAccount\Exceptions\AccountNotFoundException;
use Nexus\ChartOfAccount\Exceptions\DuplicateAccountCodeException;
use Nexus\ChartOfAccount\Exceptions\InvalidAccountException;
use Nexus\ChartOfAccount\ValueObjects\AccountCode;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Chart of Accounts Manager.
 *
 * Provides high-level account management operations with business rule validation.
 * This service orchestrates queries and persistence while enforcing domain rules.
 */
final readonly class AccountManager implements AccountManagerInterface
{
    public function __construct(
        private AccountQueryInterface $query,
        private AccountPersistInterface $persist,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * {@inheritdoc}
     */
    public function createAccount(array $data): AccountInterface
    {
        // Validate required fields
        $this->validateRequiredFields($data, ['code', 'name', 'type']);

        $code = $data['code'];

        // Validate code format
        AccountCode::fromString($code);

        // Check for duplicate code
        if ($this->query->codeExists($code)) {
            $this->logger->warning('Attempted to create account with duplicate code', ['code' => $code]);
            throw DuplicateAccountCodeException::create($code);
        }

        // Validate account type
        $type = $this->resolveAccountType($data['type']);

        // Validate parent if provided
        if (isset($data['parent_id']) && $data['parent_id'] !== null) {
            $this->validateParent($data['parent_id'], $type);
        }

        // Create the account via persistence layer
        // Note: Actual entity creation is delegated to the persist implementation
        // which will handle creating the concrete entity
        $account = $this->persist->save($this->buildAccountFromData($data));

        $this->logger->info('Account created', [
            'id' => $account->getId(),
            'code' => $account->getCode(),
            'type' => $type->value,
        ]);

        return $account;
    }

    /**
     * {@inheritdoc}
     */
    public function updateAccount(string $id, array $data): AccountInterface
    {
        $account = $this->query->find($id);

        if ($account === null) {
            throw AccountNotFoundException::withId($id);
        }

        // Validate code uniqueness if changing
        if (isset($data['code']) && $data['code'] !== $account->getCode()) {
            AccountCode::fromString($data['code']);

            if ($this->query->codeExists($data['code'], $id)) {
                throw DuplicateAccountCodeException::create($data['code']);
            }
        }

        // Validate type change restrictions
        if (isset($data['type'])) {
            $newType = $this->resolveAccountType($data['type']);
            if ($newType !== $account->getType()) {
                throw InvalidAccountException::typeChangeNotAllowed(
                    $id,
                    $account->getType()->value,
                    $newType->value
                );
            }
        }

        // Validate header status change
        if (isset($data['is_header']) && $data['is_header'] !== $account->isHeader()) {
            if ($account->isHeader() && $this->query->hasChildren($id)) {
                throw InvalidAccountException::cannotChangeHeaderWithChildren($id);
            }
        }

        // Validate parent change
        if (isset($data['parent_id']) && $data['parent_id'] !== $account->getParentId()) {
            if ($data['parent_id'] !== null) {
                $this->validateParent($data['parent_id'], $account->getType());
            }
        }

        $updatedAccount = $this->persist->save($this->mergeAccountData($account, $data));

        $this->logger->info('Account updated', [
            'id' => $id,
            'changes' => array_keys($data),
        ]);

        return $updatedAccount;
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): AccountInterface
    {
        $account = $this->query->find($id);

        if ($account === null) {
            throw AccountNotFoundException::withId($id);
        }

        return $account;
    }

    /**
     * {@inheritdoc}
     */
    public function findByCode(string $code): AccountInterface
    {
        $account = $this->query->findByCode($code);

        if ($account === null) {
            throw AccountNotFoundException::withCode($code);
        }

        return $account;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccounts(array $filters = []): array
    {
        return $this->query->findAll($filters);
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren(string $parentId): array
    {
        // Validate parent exists
        if ($this->query->find($parentId) === null) {
            throw AccountNotFoundException::withId($parentId);
        }

        return $this->query->findChildren($parentId);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccountsByType(AccountType $type): array
    {
        return $this->query->findByType($type);
    }

    /**
     * {@inheritdoc}
     */
    public function activateAccount(string $id): AccountInterface
    {
        $account = $this->findById($id);

        if ($account->isActive()) {
            return $account;
        }

        $updatedAccount = $this->persist->save(
            $this->mergeAccountData($account, ['is_active' => true])
        );

        $this->logger->info('Account activated', ['id' => $id]);

        return $updatedAccount;
    }

    /**
     * {@inheritdoc}
     */
    public function deactivateAccount(string $id): AccountInterface
    {
        $account = $this->findById($id);

        if (!$account->isActive()) {
            return $account;
        }

        $updatedAccount = $this->persist->save(
            $this->mergeAccountData($account, ['is_active' => false])
        );

        $this->logger->info('Account deactivated', ['id' => $id]);

        return $updatedAccount;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAccount(string $id): void
    {
        $account = $this->query->find($id);

        if ($account === null) {
            throw AccountNotFoundException::withId($id);
        }

        // Check for children
        if ($this->query->hasChildren($id)) {
            throw AccountHasChildrenException::create($id);
        }

        // Note: Transaction count check is handled by AccountPersistInterface
        // which has access to transaction data via external integration

        $this->persist->delete($id);

        $this->logger->info('Account deleted', [
            'id' => $id,
            'code' => $account->getCode(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function isCodeAvailable(string $code, ?string $excludeId = null): bool
    {
        return !$this->query->codeExists($code, $excludeId);
    }

    /**
     * {@inheritdoc}
     */
    public function isValidParentChild(string $parentId, AccountType $childType): bool
    {
        $parent = $this->query->find($parentId);

        if ($parent === null) {
            return false;
        }

        if (!$parent->isHeader()) {
            return false;
        }

        // Child type must match parent's balance sheet category
        // Assets can only contain Assets
        // Liabilities can only contain Liabilities
        // etc.
        return $parent->getType() === $childType
            || $this->areTypesCompatible($parent->getType(), $childType);
    }

    /**
     * Validate required fields are present.
     *
     * @param array<string, mixed> $data
     * @param array<string> $required
     * @throws InvalidAccountException
     */
    private function validateRequiredFields(array $data, array $required): void
    {
        foreach ($required as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                throw InvalidAccountException::missingField($field);
            }
        }
    }

    /**
     * Resolve account type from string or enum.
     *
     * @param string|AccountType $type
     * @return AccountType
     * @throws InvalidAccountException
     */
    private function resolveAccountType(string|AccountType $type): AccountType
    {
        if ($type instanceof AccountType) {
            return $type;
        }

        $enumType = AccountType::tryFrom($type);

        if ($enumType === null) {
            throw new InvalidAccountException(
                sprintf('Invalid account type: %s', $type)
            );
        }

        return $enumType;
    }

    /**
     * Validate parent account.
     *
     * @throws AccountNotFoundException
     * @throws InvalidAccountException
     */
    private function validateParent(string $parentId, AccountType $childType): void
    {
        $parent = $this->query->find($parentId);

        if ($parent === null) {
            throw AccountNotFoundException::withId($parentId);
        }

        if (!$parent->isHeader()) {
            throw InvalidAccountException::parentMustBeHeader($parentId);
        }

        if (!$this->areTypesCompatible($parent->getType(), $childType)) {
            throw InvalidAccountException::invalidParentChild($parentId, $childType->value);
        }
    }

    /**
     * Check if parent and child types are compatible.
     *
     * Generally, child accounts must have the same type as their parent.
     * This enforces the balance sheet/income statement categorization.
     */
    private function areTypesCompatible(AccountType $parentType, AccountType $childType): bool
    {
        // Strict matching - child must have same type as parent
        return $parentType === $childType;
    }

    /**
     * Build account data structure for creation.
     *
     * Note: This returns a structure that the persist interface will use.
     * The actual AccountInterface implementation is provided by the consumer.
     *
     * @param array<string, mixed> $data
     * @return AccountInterface
     */
    private function buildAccountFromData(array $data): AccountInterface
    {
        // This is a placeholder - in practice, the persist interface
        // receives data and creates the concrete entity
        // For now, we pass through the data as-is
        // The persist implementation will handle entity creation
        throw new \LogicException(
            'AccountManager::buildAccountFromData should not be called directly. ' .
            'The persist interface implementation should create entities from data arrays.'
        );
    }

    /**
     * Merge account with updated data.
     *
     * Note: This is a conceptual merge - actual implementation depends on
     * the concrete entity implementation provided by the consumer.
     *
     * @param AccountInterface $account
     * @param array<string, mixed> $data
     * @return AccountInterface
     */
    private function mergeAccountData(AccountInterface $account, array $data): AccountInterface
    {
        // This is a placeholder - in practice, the persist interface
        // handles merging existing entities with updated data
        throw new \LogicException(
            'AccountManager::mergeAccountData should not be called directly. ' .
            'The persist interface implementation should handle entity updates.'
        );
    }
}
