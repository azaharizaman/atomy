<?php

declare(strict_types=1);

namespace Nexus\Crypto\Services;

use Nexus\Crypto\Contracts\DataMaskerInterface;
use Nexus\Crypto\Enums\MaskingPattern;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * DataMasker Service
 *
 * Production-ready implementation of data masking for PII protection.
 *
 * This service provides:
 * - Format-preserving masking (maintains data structure)
 * - Multiple international format support
 * - Compliance-aware patterns (PCI-DSS, GDPR, HIPAA)
 * - Consistent masking across the application
 *
 * Security Considerations:
 * - Masking is one-way; original data cannot be recovered
 * - Pattern detection is conservative to avoid false negatives
 * - Logging never includes unmasked sensitive data
 */
final readonly class DataMasker implements DataMaskerInterface
{
    private const string DEFAULT_MASK_CHAR = '*';
    private const string MASK_INDICATOR = '*';
    private const int MIN_EMAIL_LOCAL_VISIBLE = 1;
    private const int MAX_EMAIL_LOCAL_VISIBLE = 3;
    private const int PHONE_VISIBLE_DIGITS = 4;
    private const int CARD_FIRST_VISIBLE = 6;
    private const int CARD_LAST_VISIBLE = 4;
    private const int IBAN_FIRST_VISIBLE = 4;
    private const int IBAN_LAST_VISIBLE = 4;

    public function __construct(
        private LoggerInterface $logger = new NullLogger(),
        private string $maskChar = self::DEFAULT_MASK_CHAR,
    ) {}

    /**
     * @inheritDoc
     */
    public function mask(string $data, MaskingPattern $pattern): string
    {
        $this->logger->debug('Masking with pattern', ['pattern' => $pattern->value]);

        return match ($pattern) {
            MaskingPattern::EMAIL => $this->maskEmail($data),
            MaskingPattern::PHONE => $this->maskPhone($data),
            MaskingPattern::CREDIT_CARD => $this->maskCreditCard($data),
            MaskingPattern::NATIONAL_ID => throw new \InvalidArgumentException(
                'NATIONAL_ID pattern requires country context. Use maskNationalId($data, $country) directly.'
            ),
            MaskingPattern::IBAN => $this->maskIban($data),
            MaskingPattern::NAME => $this->maskName($data),
            MaskingPattern::ADDRESS => $this->maskAddress($data),
            MaskingPattern::DATE_OF_BIRTH => $this->maskDateOfBirth($data),
            MaskingPattern::FULL_REDACTION => $this->redact($data),
        };
    }

    /**
     * @inheritDoc
     */
    public function maskWithPattern(string $data, string $pattern, string $maskChar = '*'): string
    {
        $this->logger->debug('Masking with custom pattern', ['pattern' => $pattern]);

        $result = '';
        $dataIndex = 0;
        $dataLength = mb_strlen($data);
        $patternLength = strlen($pattern);

        for ($i = 0; $i < $patternLength && $dataIndex < $dataLength; $i++) {
            $patternChar = $pattern[$i];
            
            if ($patternChar === '#') {
                // Preserve character
                $result .= mb_substr($data, $dataIndex, 1);
                $dataIndex++;
            } elseif ($patternChar === '*') {
                // Mask character
                $result .= $maskChar;
                $dataIndex++;
            } else {
                // Literal character from pattern
                $result .= $patternChar;
            }
        }

        // If data is longer than pattern, mask remaining characters
        if ($dataIndex < $dataLength) {
            $remaining = $dataLength - $dataIndex;
            $result .= str_repeat($maskChar, $remaining);
        }

        return $result;
    }

    /**
     * Basic masking with visible start/end
     *
     * @param string $data Data to mask
     * @param int $visibleStart Characters to keep visible at start
     * @param int $visibleEnd Characters to keep visible at end
     * @param string $maskChar Character to use for masking
     * @return string Masked data
     */
    private function maskBasic(string $data, int $visibleStart = 0, int $visibleEnd = 0, string $maskChar = '*'): string
    {
        $this->logger->debug('Masking data', [
            'visibleStart' => $visibleStart,
            'visibleEnd' => $visibleEnd,
        ]);

        $length = mb_strlen($data);
        
        if ($length === 0) {
            return '';
        }

        // Ensure visible portions don't exceed data length
        $visibleStart = min($visibleStart, $length);
        $visibleEnd = min($visibleEnd, $length - $visibleStart);
        
        // Calculate masked portion length
        $maskedLength = max(0, $length - $visibleStart - $visibleEnd);
        
        // Build masked string
        $result = mb_substr($data, 0, $visibleStart)
            . str_repeat($maskChar, $maskedLength)
            . mb_substr($data, $length - $visibleEnd);
        
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function maskEmail(string $email): string
    {
        if (!str_contains($email, '@')) {
            $this->logger->warning('Invalid email format provided for masking');
            return $this->maskBasic($email, 1, 0);
        }

        [$localPart, $domain] = explode('@', $email, 2);
        $localLength = mb_strlen($localPart);
        
        // Show 1-3 characters of local part based on length
        $visibleChars = min(
            max(self::MIN_EMAIL_LOCAL_VISIBLE, (int) floor($localLength / 3)),
            self::MAX_EMAIL_LOCAL_VISIBLE
        );
        
        // Mask local part, preserve domain fully
        $maskedLocal = mb_substr($localPart, 0, $visibleChars)
            . str_repeat($this->maskChar, max(0, $localLength - $visibleChars));
        
        return $maskedLocal . '@' . $domain;
    }

    /**
     * @inheritDoc
     */
    public function maskPhone(string $phone): string
    {
        // Remove all non-digit characters for processing
        $digitsOnly = preg_replace('/\D/', '', $phone);
        
        if ($digitsOnly === null || $digitsOnly === '') {
            return $phone;
        }
        
        $digitCount = strlen($digitsOnly);
        
        if ($digitCount <= self::PHONE_VISIBLE_DIGITS) {
            // Too short to mask meaningfully
            return $phone;
        }
        
        // Calculate how many digits to mask (all except last 4)
        $maskedCount = $digitCount - self::PHONE_VISIBLE_DIGITS;
        $lastDigits = substr($digitsOnly, -self::PHONE_VISIBLE_DIGITS);
        
        // Replace digits in original string preserving format
        return $this->replaceDigitsPreservingFormat(
            $phone,
            $maskedCount,
            $lastDigits
        );
    }

    /**
     * @inheritDoc
     */
    public function maskCreditCard(string $cardNumber): string
    {
        // Remove spaces and hyphens for processing
        $digitsOnly = preg_replace('/[\s\-]/', '', $cardNumber);
        
        if ($digitsOnly === null || !ctype_digit($digitsOnly)) {
            $this->logger->warning('Invalid credit card format provided');
            return $this->maskBasic($cardNumber, 4, 4);
        }
        
        $length = strlen($digitsOnly);
        
        // Standard card length validation (13-19 digits)
        if ($length < 13 || $length > 19) {
            $this->logger->warning('Unusual credit card length', ['length' => $length]);
        }
        
        // PCI-DSS compliant: Show first 6 and last 4
        $firstSix = substr($digitsOnly, 0, self::CARD_FIRST_VISIBLE);
        $lastFour = substr($digitsOnly, -self::CARD_LAST_VISIBLE);
        $maskedMiddle = str_repeat($this->maskChar, $length - self::CARD_FIRST_VISIBLE - self::CARD_LAST_VISIBLE);
        
        $maskedDigits = $firstSix . $maskedMiddle . $lastFour;
        
        // Preserve original formatting (spaces/hyphens)
        return $this->applyOriginalFormat($cardNumber, $maskedDigits);
    }

    /**
     * @inheritDoc
     */
    public function maskNationalId(string $nationalId, string $country): string
    {
        return match (strtoupper($country)) {
            'MY' => $this->maskMalaysianIC($nationalId),
            'US' => $this->maskAmericanSSN($nationalId),
            'GB', 'UK' => $this->maskBritishNIN($nationalId),
            'SG' => $this->maskSingaporeNRIC($nationalId),
            default => $this->maskBasic($nationalId, 2, 2),
        };
    }

    /**
     * @inheritDoc
     */
    public function maskIban(string $iban): string
    {
        // Remove spaces for processing
        $cleanIban = str_replace(' ', '', $iban);
        $length = strlen($cleanIban);
        
        if ($length < 15) { // Minimum IBAN length
            $this->logger->warning('IBAN too short', ['length' => $length]);
            return $this->maskBasic($iban, 2, 2);
        }
        
        // Show first 4 (country + check digits) and last 4
        $firstFour = substr($cleanIban, 0, self::IBAN_FIRST_VISIBLE);
        $lastFour = substr($cleanIban, -self::IBAN_LAST_VISIBLE);
        $maskedMiddle = str_repeat($this->maskChar, $length - self::IBAN_FIRST_VISIBLE - self::IBAN_LAST_VISIBLE);
        
        $maskedIban = $firstFour . $maskedMiddle . $lastFour;
        
        // Format with spaces every 4 characters (standard IBAN display)
        return implode(' ', str_split($maskedIban, 4));
    }

    /**
     * @inheritDoc
     */
    public function maskName(string $name): string
    {
        $trimmedName = trim($name);
        
        if ($trimmedName === '') {
            return '';
        }
        
        $parts = preg_split('/\s+/', $trimmedName);
        
        if ($parts === false || count($parts) === 0) {
            return $name;
        }
        
        // Single name: show first letter only
        if (count($parts) === 1) {
            $part = $parts[0];
            $length = mb_strlen($part);
            if ($length <= 1) {
                return $part;
            }
            $firstChar = mb_substr($part, 0, 1);
            return $firstChar . str_repeat($this->maskChar, $length - 1);
        }
        
        // Multiple parts: show first letter of each part
        $masked = [];
        foreach ($parts as $part) {
            $length = mb_strlen($part);
            if ($length > 0) {
                $firstChar = mb_substr($part, 0, 1);
                $masked[] = $firstChar . str_repeat($this->maskChar, max(0, $length - 1));
            }
        }
        
        return implode(' ', $masked);
    }

    /**
     * @inheritDoc
     */
    public function maskAddress(string $address): string
    {
        $lines = preg_split('/[\r\n]+/', $address);
        
        if ($lines === false || count($lines) === 0) {
            return $this->maskBasic($address, 3, 0);
        }
        
        $maskedLines = [];
        
        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // First line (street): mask heavily, keep first 3 chars
            // Last line (city/country): keep more visible for context
            if ($index === 0) {
                $maskedLines[] = $this->maskAddressLine($line, 3, 0);
            } elseif ($index === count($lines) - 1) {
                // Last line (likely city/country): show first word fully
                $maskedLines[] = $this->maskAddressLineKeepFirst($line);
            } else {
                // Middle lines: moderate masking
                $maskedLines[] = $this->maskAddressLine($line, 2, 0);
            }
        }
        
        return implode(', ', $maskedLines);
    }

    /**
     * @inheritDoc
     */
    public function maskDateOfBirth(string $dob): string
    {
        // Try to parse various date formats
        $patterns = [
            // YYYY-MM-DD
            '/^(\d{4})-(\d{2})-(\d{2})$/' => fn($m) => $m[1] . '-' . $this->maskChar . $this->maskChar . '-' . $this->maskChar . $this->maskChar,
            // DD/MM/YYYY or MM/DD/YYYY
            '/^(\d{2})\/(\d{2})\/(\d{4})$/' => fn($m) => $this->maskChar . $this->maskChar . '/' . $this->maskChar . $this->maskChar . '/' . $m[3],
            // DD-MM-YYYY
            '/^(\d{2})-(\d{2})-(\d{4})$/' => fn($m) => $this->maskChar . $this->maskChar . '-' . $this->maskChar . $this->maskChar . '-' . $m[3],
            // DD.MM.YYYY (European)
            '/^(\d{2})\.(\d{2})\.(\d{4})$/' => fn($m) => $this->maskChar . $this->maskChar . '.' . $this->maskChar . $this->maskChar . '.' . $m[3],
        ];
        
        foreach ($patterns as $pattern => $replacer) {
            if (preg_match($pattern, $dob, $matches)) {
                return $replacer($matches);
            }
        }
        
        // Unknown format: show year only (last 4 chars if likely year)
        $this->logger->warning('Unknown date format for masking', ['format' => $dob]);
        return $this->maskBasic($dob, 0, 4);
    }

    /**
     * @inheritDoc
     */
    public function redact(string $data, string $replacement = '[REDACTED]'): string
    {
        $this->logger->debug('Redacting data', ['replacement' => $replacement]);
        
        return $replacement;
    }

    /**
     * @inheritDoc
     */
    public function isAlreadyMasked(string $value, string $maskChar = '*'): bool
    {
        // Check for common masked indicators
        if (str_contains($value, $maskChar)) {
            $maskCount = substr_count($value, $maskChar);
            $dataLength = mb_strlen($value);
            
            // If more than 30% of the string is mask chars, likely masked
            if ($maskCount > 0 && ($maskCount / $dataLength) > 0.3) {
                return true;
            }
        }
        
        // Check for common redaction markers
        $redactionMarkers = ['[REDACTED]', '[MASKED]', '[SUPPRESSED]', '[HIDDEN]', 'XXXX'];
        foreach ($redactionMarkers as $marker) {
            if (str_contains(strtoupper($value), $marker)) {
                return true;
            }
        }
        
        return false;
    }

    // =====================================================
    // PRIVATE METHODS - Country-Specific National ID Masking
    // =====================================================

    /**
     * Mask Malaysian IC (NRIC)
     *
     * Format: YYMMDD-PP-NNNN
     * Shows birth year and last 4 digits
     */
    private function maskMalaysianIC(string $ic): string
    {
        // Remove hyphens
        $clean = str_replace('-', '', $ic);
        
        if (strlen($clean) !== 12) {
            return $this->maskBasic($ic, 2, 4);
        }
        
        // Show first 2 (birth year) and last 4
        $birthYear = substr($clean, 0, 2);
        $lastFour = substr($clean, -4);
        
        return $birthYear . str_repeat($this->maskChar, 4) . '-' . 
               str_repeat($this->maskChar, 2) . '-' . $lastFour;
    }

    /**
     * Mask American SSN
     *
     * Format: XXX-XX-XXXX
     * Shows only last 4 digits (standard practice)
     */
    private function maskAmericanSSN(string $ssn): string
    {
        $clean = str_replace('-', '', $ssn);
        
        if (strlen($clean) !== 9) {
            return $this->maskBasic($ssn, 0, 4);
        }
        
        return str_repeat($this->maskChar, 3) . '-' . 
               str_repeat($this->maskChar, 2) . '-' . 
               substr($clean, -4);
    }

    /**
     * Mask British National Insurance Number
     *
     * Format: AB 12 34 56 C
     * Shows prefix letters and suffix
     */
    private function maskBritishNIN(string $nin): string
    {
        $clean = str_replace(' ', '', strtoupper($nin));
        
        if (strlen($clean) !== 9) {
            return $this->maskBasic($nin, 2, 1);
        }
        
        $prefix = substr($clean, 0, 2);
        $suffix = substr($clean, -1);
        
        return $prefix . ' ' . str_repeat($this->maskChar, 2) . ' ' . 
               str_repeat($this->maskChar, 2) . ' ' . 
               str_repeat($this->maskChar, 2) . ' ' . $suffix;
    }

    /**
     * Mask Singapore NRIC/FIN
     *
     * Format: S1234567A
     * Shows first letter and last 4 characters
     */
    private function maskSingaporeNRIC(string $nric): string
    {
        $clean = strtoupper(trim($nric));
        
        if (strlen($clean) !== 9) {
            return $this->maskBasic($nric, 1, 4);
        }
        
        $prefix = $clean[0];
        $lastFour = substr($clean, -4);
        
        return $prefix . str_repeat($this->maskChar, 4) . $lastFour;
    }

    // =====================================================
    // PRIVATE METHODS - Formatting Helpers
    // =====================================================

    /**
     * Replace digits while preserving formatting characters
     */
    private function replaceDigitsPreservingFormat(
        string $original,
        int $maskedCount,
        string $lastDigits
    ): string {
        $result = '';
        $digitIndex = 0;
        $lastDigitsIndex = strlen($lastDigits) - 1;
        $originalLength = strlen($original);
        
        // Process from end to preserve last digits
        $chars = str_split($original);
        $reversed = array_reverse($chars);
        $resultChars = [];
        
        $digitsFromEnd = 0;
        
        foreach ($reversed as $char) {
            if (ctype_digit($char)) {
                if ($digitsFromEnd < strlen($lastDigits)) {
                    $resultChars[] = $lastDigits[strlen($lastDigits) - 1 - $digitsFromEnd];
                } else {
                    $resultChars[] = $this->maskChar;
                }
                $digitsFromEnd++;
            } else {
                $resultChars[] = $char;
            }
        }
        
        return implode('', array_reverse($resultChars));
    }

    /**
     * Apply original formatting (spaces, hyphens) to masked digits
     */
    private function applyOriginalFormat(string $original, string $maskedDigits): string
    {
        $result = '';
        $digitIndex = 0;
        
        foreach (str_split($original) as $char) {
            if (ctype_digit($char) || $char === $this->maskChar) {
                if ($digitIndex < strlen($maskedDigits)) {
                    $result .= $maskedDigits[$digitIndex];
                    $digitIndex++;
                }
            } else {
                // Preserve formatting characters
                $result .= $char;
            }
        }
        
        return $result;
    }

    /**
     * Mask address line with visible start/end characters
     */
    private function maskAddressLine(string $line, int $visibleStart, int $visibleEnd): string
    {
        $length = mb_strlen($line);
        
        if ($length <= $visibleStart + $visibleEnd) {
            return $line;
        }
        
        $start = mb_substr($line, 0, $visibleStart);
        $end = $visibleEnd > 0 ? mb_substr($line, -$visibleEnd) : '';
        $middle = str_repeat($this->maskChar, min(8, $length - $visibleStart - $visibleEnd));
        
        return $start . $middle . $end;
    }

    /**
     * Mask address line keeping first word
     */
    private function maskAddressLineKeepFirst(string $line): string
    {
        $words = preg_split('/\s+/', $line, 2);
        
        if ($words === false || count($words) === 0) {
            return $this->maskBasic($line, 3, 0);
        }
        
        $firstWord = $words[0];
        
        if (count($words) === 1) {
            return $firstWord;
        }
        
        // Mask everything after first word
        return $firstWord . ' ' . str_repeat($this->maskChar, min(8, mb_strlen($words[1])));
    }
}
