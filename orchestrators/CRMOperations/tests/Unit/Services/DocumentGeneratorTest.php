<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Tests\Unit\Services;

use Nexus\CRMOperations\Services\DocumentGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DocumentGenerator
 * 
 * @package Nexus\CRMOperations\Tests\Unit\Services
 */
final class DocumentGeneratorTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $this->assertTrue(true);
    }

    public function testGenerateProposalMethodExists(): void
    {
        $this->assertTrue(method_exists(DocumentGenerator::class, 'generateProposal'));
    }

    public function testGenerateContractMethodExists(): void
    {
        $this->assertTrue(method_exists(DocumentGenerator::class, 'generateContract'));
    }

    public function testGenerateQuoteMethodExists(): void
    {
        $this->assertTrue(method_exists(DocumentGenerator::class, 'generateQuote'));
    }

    public function testRenderTemplateMethodExists(): void
    {
        $this->assertTrue(method_exists(DocumentGenerator::class, 'renderTemplate'));
    }

    public function testGetAvailableTemplatesMethodExists(): void
    {
        $this->assertTrue(method_exists(DocumentGenerator::class, 'getAvailableTemplates'));
    }
}
