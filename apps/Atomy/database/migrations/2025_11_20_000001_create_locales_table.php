<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locales', function (Blueprint $table) {
            $table->string('code', 10)->primary();
            $table->string('parent_locale_code', 10)->nullable();
            $table->string('name', 100);
            $table->string('native_name', 100);
            $table->enum('text_direction', ['ltr', 'rtl'])->default('ltr');
            $table->enum('status', ['active', 'draft', 'deprecated'])->default('active');
            $table->char('decimal_separator', 1)->default('.');
            $table->char('thousands_separator', 1)->default(',');
            $table->string('date_format', 50);
            $table->string('time_format', 50);
            $table->string('datetime_format', 100);
            $table->enum('currency_position', ['before', 'after', 'before_space', 'after_space'])->default('before');
            $table->tinyInteger('first_day_of_week')->default(0); // 0 = Sunday
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('parent_locale_code')
                ->references('code')
                ->on('locales')
                ->onDelete('set null');

            $table->index('status');
        });

        // Seed CLDR-authoritative locale data (15 initial locales)
        $this->seedLocales();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locales');
    }

    /**
     * Seed initial locale data from CLDR.
     */
    private function seedLocales(): void
    {
        $locales = [
            // English (US) - System Default
            [
                'code' => 'en_US',
                'parent_locale_code' => null,
                'name' => 'English (United States)',
                'native_name' => 'English (United States)',
                'text_direction' => 'ltr',
                'status' => 'active',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'date_format' => 'M/d/yyyy',
                'time_format' => 'h:mm a',
                'datetime_format' => 'M/d/yyyy h:mm a',
                'currency_position' => 'before',
                'first_day_of_week' => 0, // Sunday
                'metadata' => json_encode([
                    'currency_symbols' => [
                        'USD' => '$',
                        'EUR' => '€',
                        'GBP' => '£',
                    ],
                ]),
            ],

            // English (Generic)
            [
                'code' => 'en',
                'parent_locale_code' => null,
                'name' => 'English',
                'native_name' => 'English',
                'text_direction' => 'ltr',
                'status' => 'active',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'date_format' => 'dd/MM/yyyy',
                'time_format' => 'HH:mm',
                'datetime_format' => 'dd/MM/yyyy HH:mm',
                'currency_position' => 'before',
                'first_day_of_week' => 1, // Monday
                'metadata' => json_encode([
                    'currency_symbols' => [
                        'USD' => '$',
                        'GBP' => '£',
                    ],
                ]),
            ],

            // Malay (Malaysia)
            [
                'code' => 'ms_MY',
                'parent_locale_code' => 'ms',
                'name' => 'Malay (Malaysia)',
                'native_name' => 'Bahasa Melayu (Malaysia)',
                'text_direction' => 'ltr',
                'status' => 'active',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'date_format' => 'd/M/yyyy',
                'time_format' => 'h:mm a',
                'datetime_format' => 'd/M/yyyy h:mm a',
                'currency_position' => 'before',
                'first_day_of_week' => 1, // Monday
                'metadata' => json_encode([
                    'currency_symbols' => [
                        'MYR' => 'RM',
                        'USD' => 'USD',
                    ],
                ]),
            ],

            // Malay (Generic)
            [
                'code' => 'ms',
                'parent_locale_code' => null,
                'name' => 'Malay',
                'native_name' => 'Bahasa Melayu',
                'text_direction' => 'ltr',
                'status' => 'active',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'date_format' => 'dd/MM/yyyy',
                'time_format' => 'HH:mm',
                'datetime_format' => 'dd/MM/yyyy HH:mm',
                'currency_position' => 'before',
                'first_day_of_week' => 1,
                'metadata' => json_encode([
                    'currency_symbols' => [
                        'MYR' => 'RM',
                    ],
                ]),
            ],

            // Chinese (Simplified, China)
            [
                'code' => 'zh_CN',
                'parent_locale_code' => 'zh',
                'name' => 'Chinese (Simplified, China)',
                'native_name' => '中文（简体，中国）',
                'text_direction' => 'ltr',
                'status' => 'active',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'date_format' => 'yyyy/M/d',
                'time_format' => 'ah:mm',
                'datetime_format' => 'yyyy/M/d ah:mm',
                'currency_position' => 'before',
                'first_day_of_week' => 1,
                'metadata' => json_encode([
                    'currency_symbols' => [
                        'CNY' => '¥',
                        'USD' => 'US$',
                    ],
                ]),
            ],

            // Chinese (Generic)
            [
                'code' => 'zh',
                'parent_locale_code' => null,
                'name' => 'Chinese',
                'native_name' => '中文',
                'text_direction' => 'ltr',
                'status' => 'active',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'date_format' => 'yyyy/M/d',
                'time_format' => 'HH:mm',
                'datetime_format' => 'yyyy/M/d HH:mm',
                'currency_position' => 'before',
                'first_day_of_week' => 1,
                'metadata' => json_encode([
                    'currency_symbols' => [
                        'CNY' => '¥',
                    ],
                ]),
            ],

            // Indonesian (Indonesia)
            [
                'code' => 'id_ID',
                'parent_locale_code' => null,
                'name' => 'Indonesian (Indonesia)',
                'native_name' => 'Bahasa Indonesia (Indonesia)',
                'text_direction' => 'ltr',
                'status' => 'active',
                'decimal_separator' => ',',
                'thousands_separator' => '.',
                'date_format' => 'dd/MM/yyyy',
                'time_format' => 'HH.mm',
                'datetime_format' => 'dd/MM/yyyy HH.mm',
                'currency_position' => 'before',
                'first_day_of_week' => 1,
                'metadata' => json_encode([
                    'currency_symbols' => [
                        'IDR' => 'Rp',
                    ],
                ]),
            ],

            // Thai (Thailand)
            [
                'code' => 'th_TH',
                'parent_locale_code' => null,
                'name' => 'Thai (Thailand)',
                'native_name' => 'ไทย (ประเทศไทย)',
                'text_direction' => 'ltr',
                'status' => 'active',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'date_format' => 'd/M/yyyy',
                'time_format' => 'HH:mm',
                'datetime_format' => 'd/M/yyyy HH:mm',
                'currency_position' => 'before',
                'first_day_of_week' => 0, // Sunday
                'metadata' => json_encode([
                    'currency_symbols' => [
                        'THB' => '฿',
                    ],
                ]),
            ],

            // Vietnamese (Vietnam)
            [
                'code' => 'vi_VN',
                'parent_locale_code' => null,
                'name' => 'Vietnamese (Vietnam)',
                'native_name' => 'Tiếng Việt (Việt Nam)',
                'text_direction' => 'ltr',
                'status' => 'active',
                'decimal_separator' => ',',
                'thousands_separator' => '.',
                'date_format' => 'dd/MM/yyyy',
                'time_format' => 'HH:mm',
                'datetime_format' => 'HH:mm dd/MM/yyyy',
                'currency_position' => 'after_space',
                'first_day_of_week' => 1,
                'metadata' => json_encode([
                    'currency_symbols' => [
                        'VND' => '₫',
                    ],
                ]),
            ],

            // Japanese (Japan)
            [
                'code' => 'ja_JP',
                'parent_locale_code' => null,
                'name' => 'Japanese (Japan)',
                'native_name' => '日本語（日本）',
                'text_direction' => 'ltr',
                'status' => 'active',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'date_format' => 'yyyy/MM/dd',
                'time_format' => 'H:mm',
                'datetime_format' => 'yyyy/MM/dd H:mm',
                'currency_position' => 'before',
                'first_day_of_week' => 0,
                'metadata' => json_encode([
                    'currency_symbols' => [
                        'JPY' => '¥',
                    ],
                ]),
            ],

            // Korean (South Korea)
            [
                'code' => 'ko_KR',
                'parent_locale_code' => null,
                'name' => 'Korean (South Korea)',
                'native_name' => '한국어 (대한민국)',
                'text_direction' => 'ltr',
                'status' => 'active',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'date_format' => 'yyyy. M. d.',
                'time_format' => 'a h:mm',
                'datetime_format' => 'yyyy. M. d. a h:mm',
                'currency_position' => 'before',
                'first_day_of_week' => 0,
                'metadata' => json_encode([
                    'currency_symbols' => [
                        'KRW' => '₩',
                    ],
                ]),
            ],

            // Arabic (Saudi Arabia) - RTL example
            [
                'code' => 'ar_SA',
                'parent_locale_code' => null,
                'name' => 'Arabic (Saudi Arabia)',
                'native_name' => 'العربية (المملكة العربية السعودية)',
                'text_direction' => 'rtl',
                'status' => 'active',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'date_format' => 'd/M/yyyy',
                'time_format' => 'h:mm a',
                'datetime_format' => 'd/M/yyyy h:mm a',
                'currency_position' => 'after_space',
                'first_day_of_week' => 0,
                'metadata' => json_encode([
                    'currency_symbols' => [
                        'SAR' => 'ر.س',
                    ],
                ]),
            ],

            // French (France)
            [
                'code' => 'fr_FR',
                'parent_locale_code' => null,
                'name' => 'French (France)',
                'native_name' => 'Français (France)',
                'text_direction' => 'ltr',
                'status' => 'active',
                'decimal_separator' => ',',
                'thousands_separator' => ' ', // Non-breaking space in actual CLDR
                'date_format' => 'dd/MM/yyyy',
                'time_format' => 'HH:mm',
                'datetime_format' => 'dd/MM/yyyy HH:mm',
                'currency_position' => 'after_space',
                'first_day_of_week' => 1,
                'metadata' => json_encode([
                    'currency_symbols' => [
                        'EUR' => '€',
                    ],
                ]),
            ],

            // German (Germany)
            [
                'code' => 'de_DE',
                'parent_locale_code' => null,
                'name' => 'German (Germany)',
                'native_name' => 'Deutsch (Deutschland)',
                'text_direction' => 'ltr',
                'status' => 'active',
                'decimal_separator' => ',',
                'thousands_separator' => '.',
                'date_format' => 'dd.MM.yyyy',
                'time_format' => 'HH:mm',
                'datetime_format' => 'dd.MM.yyyy HH:mm',
                'currency_position' => 'after_space',
                'first_day_of_week' => 1,
                'metadata' => json_encode([
                    'currency_symbols' => [
                        'EUR' => '€',
                    ],
                ]),
            ],

            // Spanish (Spain)
            [
                'code' => 'es_ES',
                'parent_locale_code' => null,
                'name' => 'Spanish (Spain)',
                'native_name' => 'Español (España)',
                'text_direction' => 'ltr',
                'status' => 'active',
                'decimal_separator' => ',',
                'thousands_separator' => '.',
                'date_format' => 'd/M/yyyy',
                'time_format' => 'H:mm',
                'datetime_format' => 'd/M/yyyy H:mm',
                'currency_position' => 'after_space',
                'first_day_of_week' => 1,
                'metadata' => json_encode([
                    'currency_symbols' => [
                        'EUR' => '€',
                    ],
                ]),
            ],
        ];

        foreach ($locales as $locale) {
            DB::table('locales')->insert(array_merge($locale, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
};
