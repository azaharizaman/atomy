<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Enums;

/**
 * Sanctions lists from various regulatory authorities
 * 
 * Supports major international sanctions lists for comprehensive screening
 */
enum SanctionsList: string
{
    /**
     * US Office of Foreign Assets Control (OFAC)
     * - Specially Designated Nationals (SDN) List
     * - Consolidated Sanctions List
     * - Most comprehensive US sanctions list
     */
    case OFAC = 'ofac';
    
    /**
     * United Nations Security Council Consolidated List
     * - Individuals and entities subject to UN sanctions
     * - Al-Qaida, ISIS, Taliban sanctions
     * - International consensus list
     */
    case UN = 'un';
    
    /**
     * European Union Consolidated Financial Sanctions List
     * - EU-wide restrictive measures
     * - Asset freezes and travel bans
     * - Terrorism financing prevention
     */
    case EU = 'eu';
    
    /**
     * UK HM Treasury Financial Sanctions List
     * - UK Office of Financial Sanctions Implementation (OFSI)
     * - Post-Brexit UK-specific sanctions
     * - Asset freeze targets
     */
    case UK_HMT = 'uk_hmt';
    
    /**
     * Australian Department of Foreign Affairs and Trade
     * - Australian sanctions targets
     * - Autonomous sanctions regime
     */
    case AU_DFAT = 'au_dfat';
    
    /**
     * Canadian Office of the Superintendent of Financial Institutions
     * - Canadian sanctions list
     * - Asset freeze and dealings prohibitions
     */
    case CA_OSFI = 'ca_osfi';
    
    /**
     * Japan Ministry of Economy, Trade and Industry
     * - Japanese sanctions targets
     * - Export control and asset freeze
     */
    case JP_METI = 'jp_meti';
    
    /**
     * Switzerland State Secretariat for Economic Affairs (SECO)
     * - Swiss sanctions ordinances
     * - Financial sanctions implementation
     */
    case CH_SECO = 'ch_seco';
    
    /**
     * Get human-readable name of the sanctions list
     */
    public function getName(): string
    {
        return match ($this) {
            self::OFAC => 'US OFAC (Office of Foreign Assets Control)',
            self::UN => 'United Nations Security Council',
            self::EU => 'European Union Financial Sanctions',
            self::UK_HMT => 'UK HM Treasury OFSI',
            self::AU_DFAT => 'Australian DFAT Sanctions',
            self::CA_OSFI => 'Canadian OSFI Sanctions',
            self::JP_METI => 'Japan METI Sanctions',
            self::CH_SECO => 'Switzerland SECO Sanctions',
        };
    }
    
    /**
     * Get the regulatory authority issuing the list
     */
    public function getAuthority(): string
    {
        return match ($this) {
            self::OFAC => 'United States Department of the Treasury',
            self::UN => 'United Nations Security Council',
            self::EU => 'European Union',
            self::UK_HMT => 'United Kingdom HM Treasury',
            self::AU_DFAT => 'Australian Department of Foreign Affairs and Trade',
            self::CA_OSFI => 'Canadian Office of the Superintendent of Financial Institutions',
            self::JP_METI => 'Japan Ministry of Economy, Trade and Industry',
            self::CH_SECO => 'Switzerland State Secretariat for Economic Affairs',
        };
    }
    
    /**
     * Get the jurisdiction of the sanctions list
     */
    public function getJurisdiction(): string
    {
        return match ($this) {
            self::OFAC => 'US',
            self::UN => 'International',
            self::EU => 'EU',
            self::UK_HMT => 'UK',
            self::AU_DFAT => 'Australia',
            self::CA_OSFI => 'Canada',
            self::JP_METI => 'Japan',
            self::CH_SECO => 'Switzerland',
        };
    }
    
    /**
     * Check if this is a high-priority list (mandatory for most jurisdictions)
     */
    public function isHighPriority(): bool
    {
        return match ($this) {
            self::OFAC, self::UN, self::EU, self::UK_HMT => true,
            default => false,
        };
    }
}
