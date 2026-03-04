<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

use Nexus\Reporting\ValueObjects\Category;
use Nexus\Reporting\ValueObjects\Priority;

interface NotificationInterface
{
    /** 
     * @return array{
     *     subject: string,
     *     body: string,
     *     from?: string,
     *     replyTo?: string,
     *     attachments?: array<int, array{path: string, name: string, mime: string}>
     * } 
     */
    public function toEmail(): array;

    public function toSms(): string;

    /** 
     * @return array{
     *     title: string,
     *     body: string,
     *     data?: array<string, mixed>
     * } 
     */
    public function toPush(): array;

    /** 
     * @return array{
     *     id: string,
     *     title: string,
     *     body: string,
     *     metadata?: array<string, mixed>
     * } 
     */
    public function toInApp(): array;

    public function getPriority(): Priority;

    public function getCategory(): Category;
}
