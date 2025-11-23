<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Tests\Unit\Core\Decorators;

use Nexus\FeatureFlags\Contracts\FlagCacheInterface;
use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;
use Nexus\FeatureFlags\Core\Decorators\CachedFlagRepository;
use Nexus\FeatureFlags\Enums\FlagStrategy;
use Nexus\FeatureFlags\Exceptions\StaleCacheException;
use Nexus\FeatureFlags\ValueObjects\FlagDefinition;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class CachedFlagRepositoryTest extends TestCase
{
    // ========================================
    // Cache Hit Tests
    // ========================================

    public function test_find_returns_cached_flag_on_cache_hit(): void
    {
        $flag = new FlagDefinition(
            name: 'test.flag',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE
        );

        $cache = $this->createMock(FlagCacheInterface::class);
        $cache->method('buildKey')->willReturn('ff:global:flag:test.flag');
        $cache->expects($this->once())
            ->method('get')
            ->with('ff:global:flag:test.flag')
            ->willReturn($flag);

        $inner = $this->createMock(FlagRepositoryInterface::class);
        $inner->expects($this->never())->method('find');

        $logger = $this->createStub(LoggerInterface::class);

        $repository = new CachedFlagRepository($inner, $cache, $logger);

        $result = $repository->find('test.flag');

        $this->assertSame($flag, $result);
    }

    public function test_find_fetches_from_repository_on_cache_miss(): void
    {
        $flag = new FlagDefinition(
            name: 'test.flag',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE
        );

        $cache = $this->createMock(FlagCacheInterface::class);
        $cache->method('buildKey')->willReturn('ff:global:flag:test.flag');
        $cache->expects($this->once())
            ->method('get')
            ->willReturn(null); // Cache miss
        $cache->expects($this->once())
            ->method('set')
            ->with('ff:global:flag:test.flag', $flag, 300);

        $inner = $this->createMock(FlagRepositoryInterface::class);
        $inner->expects($this->once())
            ->method('find')
            ->with('test.flag', null)
            ->willReturn($flag);

        $logger = $this->createStub(LoggerInterface::class);

        $repository = new CachedFlagRepository($inner, $cache, $logger);

        $result = $repository->find('test.flag');

        $this->assertSame($flag, $result);
    }

    public function test_find_caches_fetched_flag_with_custom_ttl(): void
    {
        $flag = new FlagDefinition(
            name: 'test.flag',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE
        );

        $cache = $this->createMock(FlagCacheInterface::class);
        $cache->method('buildKey')->willReturn('ff:global:flag:test.flag');
        $cache->method('get')->willReturn(null);
        $cache->expects($this->once())
            ->method('set')
            ->with('ff:global:flag:test.flag', $flag, 600); // Custom TTL

        $inner = $this->createStub(FlagRepositoryInterface::class);
        $inner->method('find')->willReturn($flag);

        $logger = $this->createStub(LoggerInterface::class);

        $repository = new CachedFlagRepository($inner, $cache, $logger, ttl: 600);

        $repository->find('test.flag');
    }

    public function test_find_returns_null_when_flag_not_found(): void
    {
        $cache = $this->createMock(FlagCacheInterface::class);
        $cache->method('buildKey')->willReturn('ff:global:flag:nonexistent');
        $cache->method('get')->willReturn(null);
        $cache->expects($this->never())->method('set');

        $inner = $this->createStub(FlagRepositoryInterface::class);
        $inner->method('find')->willReturn(null);

        $logger = $this->createStub(LoggerInterface::class);

        $repository = new CachedFlagRepository($inner, $cache, $logger);

        $result = $repository->find('nonexistent');

        $this->assertNull($result);
    }

    // ========================================
    // Checksum Validation Tests
    // ========================================

    public function test_find_evicts_stale_cache_and_refetches_on_checksum_mismatch(): void
    {
        // Create cached flag (old version)
        $cachedFlag = $this->createStub(FlagDefinitionInterface::class);
        $cachedFlag->method('getName')->willReturn('test.flag');
        $cachedFlag->method('isEnabled')->willReturn(true);
        $cachedFlag->method('getStrategy')->willReturn(FlagStrategy::SYSTEM_WIDE);
        $cachedFlag->method('getValue')->willReturn(null);
        $cachedFlag->method('getOverride')->willReturn(null);
        $cachedFlag->method('getChecksum')->willReturn('old-checksum');

        // Fresh flag from repository
        $freshFlag = new FlagDefinition(
            name: 'test.flag',
            enabled: false, // Changed!
            strategy: FlagStrategy::SYSTEM_WIDE
        );

        $cache = $this->createMock(FlagCacheInterface::class);
        $cache->method('buildKey')->willReturn('ff:global:flag:test.flag');
        $cache->expects($this->once())
            ->method('get')
            ->willReturn($cachedFlag); // Stale cache
        $cache->expects($this->once())
            ->method('delete')
            ->with('ff:global:flag:test.flag'); // Eviction
        $cache->expects($this->once())
            ->method('set')
            ->with('ff:global:flag:test.flag', $freshFlag, 300); // Re-cache

        $inner = $this->createMock(FlagRepositoryInterface::class);
        $inner->expects($this->once())
            ->method('find')
            ->willReturn($freshFlag);

        $logger = $this->createStub(LoggerInterface::class);

        $repository = new CachedFlagRepository($inner, $cache, $logger);

        $result = $repository->find('test.flag');

        $this->assertSame($freshFlag, $result);
    }

    // ========================================
    // Bulk Operations Tests
    // ========================================

    public function test_findMany_returns_cached_flags_and_fetches_missing(): void
    {
        $flag1 = new FlagDefinition(
            name: 'flag.one',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE
        );

        $flag2 = new FlagDefinition(
            name: 'flag.two',
            enabled: false,
            strategy: FlagStrategy::SYSTEM_WIDE
        );

        $cache = $this->createMock(FlagCacheInterface::class);
        $cache->method('buildKey')->willReturnCallback(
            fn($name) => "ff:global:flag:{$name}"
        );
        $cache->expects($this->once())
            ->method('getMultiple')
            ->willReturn([
                'ff:global:flag:flag.one' => $flag1, // Cached
                'ff:global:flag:flag.two' => null,   // Not cached
            ]);
        $cache->expects($this->once())
            ->method('set')
            ->with('ff:global:flag:flag.two', $flag2, 300);

        $inner = $this->createMock(FlagRepositoryInterface::class);
        $inner->expects($this->once())
            ->method('findMany')
            ->with(['flag.two'], null)
            ->willReturn(['flag.two' => $flag2]);

        $logger = $this->createStub(LoggerInterface::class);

        $repository = new CachedFlagRepository($inner, $cache, $logger);

        $results = $repository->findMany(['flag.one', 'flag.two']);

        $this->assertSame([
            'flag.one' => $flag1,
            'flag.two' => $flag2,
        ], $results);
    }

    public function test_findMany_evicts_stale_flags_in_bulk(): void
    {
        // Stale cached flag
        $staleFlag = $this->createStub(FlagDefinitionInterface::class);
        $staleFlag->method('getName')->willReturn('stale.flag');
        $staleFlag->method('isEnabled')->willReturn(true);
        $staleFlag->method('getStrategy')->willReturn(FlagStrategy::SYSTEM_WIDE);
        $staleFlag->method('getValue')->willReturn(null);
        $staleFlag->method('getOverride')->willReturn(null);
        $staleFlag->method('getChecksum')->willReturn('old-checksum');

        // Fresh flag
        $freshFlag = new FlagDefinition(
            name: 'stale.flag',
            enabled: false, // Changed
            strategy: FlagStrategy::SYSTEM_WIDE
        );

        $cache = $this->createMock(FlagCacheInterface::class);
        $cache->method('buildKey')->willReturn('ff:global:flag:stale.flag');
        $cache->method('getMultiple')->willReturn([
            'ff:global:flag:stale.flag' => $staleFlag,
        ]);
        $cache->expects($this->once())
            ->method('deleteMultiple')
            ->with(['ff:global:flag:stale.flag']);
        $cache->expects($this->once())
            ->method('set')
            ->with('ff:global:flag:stale.flag', $freshFlag, 300);

        $inner = $this->createStub(FlagRepositoryInterface::class);
        $inner->method('findMany')->willReturn(['stale.flag' => $freshFlag]);

        $logger = $this->createStub(LoggerInterface::class);

        $repository = new CachedFlagRepository($inner, $cache, $logger);

        $results = $repository->findMany(['stale.flag']);

        $this->assertSame(['stale.flag' => $freshFlag], $results);
    }

    public function test_findMany_returns_empty_array_for_empty_input(): void
    {
        $cache = $this->createStub(FlagCacheInterface::class);
        $inner = $this->createStub(FlagRepositoryInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $repository = new CachedFlagRepository($inner, $cache, $logger);

        $results = $repository->findMany([]);

        $this->assertSame([], $results);
    }

    // ========================================
    // Cache Invalidation Tests
    // ========================================

    public function test_save_invalidates_cache_after_updating_repository(): void
    {
        $flag = new FlagDefinition(
            name: 'test.flag',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE
        );

        $cache = $this->createMock(FlagCacheInterface::class);
        $cache->method('buildKey')->willReturn('ff:global:flag:test.flag');
        $cache->expects($this->once())
            ->method('delete')
            ->with('ff:global:flag:test.flag');

        $inner = $this->createMock(FlagRepositoryInterface::class);
        $inner->expects($this->once())
            ->method('save')
            ->with($flag);

        $logger = $this->createStub(LoggerInterface::class);

        $repository = new CachedFlagRepository($inner, $cache, $logger);

        $repository->save($flag);
    }

    public function test_delete_invalidates_cache_after_deleting_from_repository(): void
    {
        $cache = $this->createMock(FlagCacheInterface::class);
        $cache->method('buildKey')->willReturn('ff:global:flag:test.flag');
        $cache->expects($this->once())
            ->method('delete')
            ->with('ff:global:flag:test.flag');

        $inner = $this->createMock(FlagRepositoryInterface::class);
        $inner->expects($this->once())
            ->method('delete')
            ->with('test.flag');

        $logger = $this->createStub(LoggerInterface::class);

        $repository = new CachedFlagRepository($inner, $cache, $logger);

        $repository->delete('test.flag');
    }

    // ========================================
    // Tenant Scoping Tests
    // ========================================

    public function test_find_uses_tenant_specific_cache_key(): void
    {
        $flag = new FlagDefinition(
            name: 'test.flag',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE
        );

        $cache = $this->createMock(FlagCacheInterface::class);
        $cache->expects($this->once())
            ->method('buildKey')
            ->with('test.flag', 'tenant-123')
            ->willReturn('ff:tenant:tenant-123:flag:test.flag');
        $cache->method('get')->willReturn(null);
        $cache->expects($this->once())
            ->method('set')
            ->with('ff:tenant:tenant-123:flag:test.flag', $flag, 300);

        $inner = $this->createStub(FlagRepositoryInterface::class);
        $inner->method('find')->willReturn($flag);

        $logger = $this->createStub(LoggerInterface::class);

        $repository = new CachedFlagRepository($inner, $cache, $logger);

        $repository->find('test.flag', 'tenant-123');
    }

    // ========================================
    // Passthrough Tests (No Caching)
    // ========================================

    public function test_all_bypasses_cache_and_calls_repository_directly(): void
    {
        $flags = [
            new FlagDefinition(name: 'flag.one', enabled: true, strategy: FlagStrategy::SYSTEM_WIDE),
            new FlagDefinition(name: 'flag.two', enabled: false, strategy: FlagStrategy::SYSTEM_WIDE),
        ];

        $cache = $this->createMock(FlagCacheInterface::class);
        $cache->expects($this->never())->method('getMultiple');
        $cache->expects($this->never())->method('set');

        $inner = $this->createMock(FlagRepositoryInterface::class);
        $inner->expects($this->once())
            ->method('all')
            ->with('tenant-123')
            ->willReturn(['flag.one' => $flags[0], 'flag.two' => $flags[1]]);

        $logger = $this->createStub(LoggerInterface::class);

        $repository = new CachedFlagRepository($inner, $cache, $logger);

        $results = $repository->all('tenant-123');

        $this->assertCount(2, $results);
    }

    // ========================================
    // Logging Tests
    // ========================================

    public function test_find_logs_cache_hit(): void
    {
        $flag = new FlagDefinition(
            name: 'test.flag',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE
        );

        $cache = $this->createStub(FlagCacheInterface::class);
        $cache->method('buildKey')->willReturn('ff:global:flag:test.flag');
        $cache->method('get')->willReturn($flag);

        $inner = $this->createStub(FlagRepositoryInterface::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with(
                'Feature flag cache hit',
                ['flag' => 'test.flag', 'tenant_id' => null]
            );

        $repository = new CachedFlagRepository($inner, $cache, $logger);

        $repository->find('test.flag');
    }

    public function test_find_logs_stale_cache_eviction(): void
    {
        $staleFlag = $this->createStub(FlagDefinitionInterface::class);
        $staleFlag->method('getName')->willReturn('test.flag');
        $staleFlag->method('isEnabled')->willReturn(true);
        $staleFlag->method('getStrategy')->willReturn(FlagStrategy::SYSTEM_WIDE);
        $staleFlag->method('getValue')->willReturn(null);
        $staleFlag->method('getOverride')->willReturn(null);
        $staleFlag->method('getChecksum')->willReturn('old-checksum');

        $freshFlag = new FlagDefinition(
            name: 'test.flag',
            enabled: false,
            strategy: FlagStrategy::SYSTEM_WIDE
        );

        $cache = $this->createStub(FlagCacheInterface::class);
        $cache->method('buildKey')->willReturn('ff:global:flag:test.flag');
        $cache->method('get')->willReturn($staleFlag);

        $inner = $this->createStub(FlagRepositoryInterface::class);
        $inner->method('find')->willReturn($freshFlag);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with(
                'Stale feature flag cache evicted',
                $this->callback(function (array $ctx) {
                    return $ctx['flag'] === 'test.flag'
                        && isset($ctx['error']);
                })
            );

        $repository = new CachedFlagRepository($inner, $cache, $logger);

        $repository->find('test.flag');
    }
}
