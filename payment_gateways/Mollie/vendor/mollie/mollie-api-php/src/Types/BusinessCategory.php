<?php

namespace Mollie\Api\Types;

use Mollie\Api\Traits\GetAllConstants;

class BusinessCategory
{
    use GetAllConstants;

    // Animal Services
    public const PET_SHOPS = 'PET_SHOPS';

    public const VETERINARY_SERVICES = 'VETERINARY_SERVICES';

    // Building Services
    public const AC_AND_HEATING_CONTRACTORS = 'AC_AND_HEATING_CONTRACTORS';

    public const CARPENTRY_CONTRACTORS = 'CARPENTRY_CONTRACTORS';

    public const ELECTRICAL_CONTRACTORS = 'ELECTRICAL_CONTRACTORS';

    public const EQUIPMENT_TOOLS_FURNITURE_RENTAL_LEASING = 'EQUIPMENT_TOOLS_FURNITURE_RENTAL_LEASING';

    public const GENERAL_CONTRACTORS = 'GENERAL_CONTRACTORS';

    public const SPECIAL_TRADE_CONTRACTORS = 'SPECIAL_TRADE_CONTRACTORS';

    // Charity and Donations
    public const CHARITY_AND_DONATIONS = 'CHARITY_AND_DONATIONS';

    public const FUNDRAISING_CROWDFUNDING_SOCIAL_SERVICE = 'FUNDRAISING_CROWDFUNDING_SOCIAL_SERVICE';

    // Digital Products
    public const APPS = 'APPS';

    public const BOOKS_MEDIA_MOVIES_MUSIC = 'BOOKS_MEDIA_MOVIES_MUSIC';

    public const GAMES = 'GAMES';

    public const SOFTWARE_AND_SUBSCRIPTIONS = 'SOFTWARE_AND_SUBSCRIPTIONS';

    // Education
    public const CHILD_CARE_SERVICES = 'CHILD_CARE_SERVICES';

    public const COLLEGES_UNIVERSITIES = 'COLLEGES_UNIVERSITIES';

    public const ELEMENTARY_SECONDARY_SCHOOLS = 'ELEMENTARY_SECONDARY_SCHOOLS';

    public const OTHER_EDUCATIONAL_SERVICES = 'OTHER_EDUCATIONAL_SERVICES';

    public const VOCATIONAL_SCHOOLS_TRADE_SCHOOLS = 'VOCATIONAL_SCHOOLS_TRADE_SCHOOLS';

    // Entertainment and Recreation
    public const AMUSEMENT_PARKS = 'AMUSEMENT_PARKS';

    public const EVENT_TICKETING = 'EVENT_TICKETING';

    public const GAMING_ESTABLISHMENTS = 'GAMING_ESTABLISHMENTS';

    public const MOVIE_THEATRES = 'MOVIE_THEATRES';

    public const MUSICIANS_BANDS_ORCHESTRAS = 'MUSICIANS_BANDS_ORCHESTRAS';

    public const ONLINE_GAMBLING = 'ONLINE_GAMBLING';

    public const OTHER_ENTERTAINMENT_RECREATION = 'OTHER_ENTERTAINMENT_RECREATION';

    public const SPORTING_RECREATIONAL_CAMPS = 'SPORTING_RECREATIONAL_CAMPS';

    public const SPORTS_FORECASTING = 'SPORTS_FORECASTING';

    // Financial Services
    public const CREDIT_COUNSELLING_REPAIR = 'CREDIT_COUNSELLING_REPAIR';

    public const DIGITAL_WALLETS = 'DIGITAL_WALLETS';

    public const INVESTMENT_SERVICES = 'INVESTMENT_SERVICES';

    public const MONEY_SERVICES = 'MONEY_SERVICES';

    public const MORTGAGES_INSURANCES_LOANS_FINANCIAL_ADVICE = 'MORTGAGES_INSURANCES_LOANS_FINANCIAL_ADVICE';

    public const SECURITY_BROKERS_DEALERS = 'SECURITY_BROKERS_DEALERS';

    public const TRUST_OFFICES = 'TRUST_OFFICES';

    public const VIRTUAL_CRYPTO_CURRENCIES = 'VIRTUAL_CRYPTO_CURRENCIES';

    // Food and Drink
    public const CATERERS = 'CATERERS';

    public const FAST_FOOD_RESTAURANTS = 'FAST_FOOD_RESTAURANTS';

    public const FOOD_PRODUCT_STORES = 'FOOD_PRODUCT_STORES';

    public const RESTAURANTS_NIGHTLIFE = 'RESTAURANTS_NIGHTLIFE';

    // Lodging and Hospitality
    public const BOAT_RENTALS_LEASING = 'BOAT_RENTALS_LEASING';

    public const CRUISE_LINES = 'CRUISE_LINES';

    public const LODGING = 'LODGING';

    public const PROPERTY_RENTALS_CAMPING = 'PROPERTY_RENTALS_CAMPING';

    // Marketplaces
    public const MARKETPLACES = 'MARKETPLACES';

    // Medical Services
    public const DENTAL_EQUIPMENT_SUPPLIES = 'DENTAL_EQUIPMENT_SUPPLIES';

    public const DENTISTS_ORTHODONTISTS = 'DENTISTS_ORTHODONTISTS';

    public const MEDICAL_SERVICES = 'MEDICAL_SERVICES';

    public const DRUG_PHARMACIES_PRESCRIPTION = 'DRUG_PHARMACIES_PRESCRIPTION';

    public const MEDICAL_DEVICES = 'MEDICAL_DEVICES';

    public const MEDICAL_ORGANIZATIONS = 'MEDICAL_ORGANIZATIONS';

    public const MENTAL_HEALTH_SERVICES = 'MENTAL_HEALTH_SERVICES';

    public const NURSING = 'NURSING';

    public const OPTICIANS_EYEGLASSES = 'OPTICIANS_EYEGLASSES';

    // Membership Organizations
    public const SOCIAL_ASSOCIATIONS = 'SOCIAL_ASSOCIATIONS';

    public const MEMBERSHIP_FEE_BASED_SPORTS = 'MEMBERSHIP_FEE_BASED_SPORTS';

    public const OTHER_MEMBERSHIP_ORGANIZATIONS = 'OTHER_MEMBERSHIP_ORGANIZATIONS';

    // Personal Services
    public const ADULT_CONTENT_SERVICES = 'ADULT_CONTENT_SERVICES';

    public const COUNSELING_SERVICES = 'COUNSELING_SERVICES';

    public const DATING_SERVICES = 'DATING_SERVICES';

    public const HEALTH_BEAUTY_SPAS = 'HEALTH_BEAUTY_SPAS';

    public const LANDSCAPING_SERVICES = 'LANDSCAPING_SERVICES';

    public const LAUNDRY_DRYCLEANING_SERVICES = 'LAUNDRY_DRYCLEANING_SERVICES';

    public const MASSAGE_PARLOURS = 'MASSAGE_PARLOURS';

    public const OTHER_PERSONAL_SERVICES = 'OTHER_PERSONAL_SERVICES';

    public const PHOTOGRAPHY_STUDIOS = 'PHOTOGRAPHY_STUDIOS';

    public const SALONS_BARBERS = 'SALONS_BARBERS';

    // Political Organizations
    public const POLITICAL_PARTIES = 'POLITICAL_PARTIES';

    // Professional Services
    public const ACCOUNTING_AUDITING_BOOKKEEPING_TAX_PREPARATION_SERVICES = 'ACCOUNTING_AUDITING_BOOKKEEPING_TAX_PREPARATION_SERVICES';

    public const ADVERTISING_SERVICES = 'ADVERTISING_SERVICES';

    public const CLEANING_MAINTENANCE_JANITORIAL_SERVICES = 'CLEANING_MAINTENANCE_JANITORIAL_SERVICES';

    public const COMPUTER_REPAIR = 'COMPUTER_REPAIR';

    public const CONSULTANCY = 'CONSULTANCY';

    public const SECURITY_SERVICES = 'SECURITY_SERVICES';

    public const DIRECT_MARKETING = 'DIRECT_MARKETING';

    public const FUNERAL_SERVICES = 'FUNERAL_SERVICES';

    public const GOVERNMENT_SERVICES = 'GOVERNMENT_SERVICES';

    public const HOSTING_VPN_SERVICES = 'HOSTING_VPN_SERVICES';

    public const INDUSTRIAL_SUPPLIES_NOT_ELSEWHERE_CLASSIFIED = 'INDUSTRIAL_SUPPLIES_NOT_ELSEWHERE_CLASSIFIED';

    public const LEGAL_SERVICES_ATTORNEYS = 'LEGAL_SERVICES_ATTORNEYS';

    public const MOTION_PICTURES_DISTRIBUTION = 'MOTION_PICTURES_DISTRIBUTION';

    public const OTHER_BUSINESS_SERVICES = 'OTHER_BUSINESS_SERVICES';

    public const PRINTING_PUBLISHING = 'PRINTING_PUBLISHING';

    public const REAL_ESTATE_AGENTS = 'REAL_ESTATE_AGENTS';

    public const SANITATION_POLISHING_SPECIALTY_CLEANING = 'SANITATION_POLISHING_SPECIALTY_CLEANING';

    public const OFFICE_SUPPLIES = 'OFFICE_SUPPLIES';

    public const TESTING_LABORATORIES_NOT_MEDICAL = 'TESTING_LABORATORIES_NOT_MEDICAL';

    public const TRAINING_AND_COACHING = 'TRAINING_AND_COACHING';

    public const UTILITIES = 'UTILITIES';

    // Religious Organizations
    public const RELIGIOUS_ORGANIZATIONS = 'RELIGIOUS_ORGANIZATIONS';

    // Retail
    public const CLOTHING_SHOES_ACCESSORIES = 'CLOTHING_SHOES_ACCESSORIES';

    public const COMMERCIAL_ART = 'COMMERCIAL_ART';

    public const BEAUTY_PRODUCTS = 'BEAUTY_PRODUCTS';

    public const BOOKS_PERIODICALS_NEWSPAPERS = 'BOOKS_PERIODICALS_NEWSPAPERS';

    public const HOME_IMPROVEMENT = 'HOME_IMPROVEMENT';

    public const GIFTS_SHOPS = 'GIFTS_SHOPS';

    public const CBD_MARIJUANA_PRODUCTS = 'CBD_MARIJUANA_PRODUCTS';

    public const COFFEE_SHOPS = 'COFFEE_SHOPS';

    public const CONVENIENCE_STORES = 'CONVENIENCE_STORES';

    public const GIFT_CARDS = 'GIFT_CARDS';

    public const EROTIC_TOYS = 'EROTIC_TOYS';

    public const FLORISTS = 'FLORISTS';

    public const FUEL_DEALERS = 'FUEL_DEALERS';

    public const FURNITURE_FURNISHINGS_EQUIPMENT_STORES = 'FURNITURE_FURNISHINGS_EQUIPMENT_STORES';

    public const GAME_TOY_HOBBY_SHOPS = 'GAME_TOY_HOBBY_SHOPS';

    public const OUTDOOR_EQUIPMENT = 'OUTDOOR_EQUIPMENT';

    public const HOME_ELECTRONICS = 'HOME_ELECTRONICS';

    public const HOUSEHOLD_APPLIANCE_STORES = 'HOUSEHOLD_APPLIANCE_STORES';

    public const JEWELRY_WATCH_CLOCK_AND_SILVERWARE_STORES_UNDER_1000 = 'JEWELRY_WATCH_CLOCK_AND_SILVERWARE_STORES_UNDER_1000';

    public const MUSIC_STORES = 'MUSIC_STORES';

    public const OTHER_MERCHANDISE = 'OTHER_MERCHANDISE';

    public const LIQUOR_STORES = 'LIQUOR_STORES';

    public const PAID_TELEVISION_RADIO = 'PAID_TELEVISION_RADIO';

    public const PRECIOUS_STONES_METALS_JEWELRY_OVER_1000 = 'PRECIOUS_STONES_METALS_JEWELRY_OVER_1000';

    public const REPAIR_SHOPS = 'REPAIR_SHOPS';

    public const SECOND_HAND_STORES = 'SECOND_HAND_STORES';

    public const SPORTING_GOODS_SPECIALTY_RETAIL_SHOPS = 'SPORTING_GOODS_SPECIALTY_RETAIL_SHOPS';

    public const SUPPLEMENTS_STORES = 'SUPPLEMENTS_STORES';

    public const TELECOM_EQUIPMENT = 'TELECOM_EQUIPMENT';

    public const TELECOM_SERVICES = 'TELECOM_SERVICES';

    public const TOBACCO_PRODUCTS = 'TOBACCO_PRODUCTS';

    public const TRADERS_DIAMONDS = 'TRADERS_DIAMONDS';

    public const TRADERS_GOLD = 'TRADERS_GOLD';

    public const WEAPONS_AMMUNITION = 'WEAPONS_AMMUNITION';

    // Transportation
    public const COMMUTER_TRANSPORTATION = 'COMMUTER_TRANSPORTATION';

    public const COURIER_SERVICES = 'COURIER_SERVICES';

    public const OTHER_TRANSPORTATION_SERVICES = 'OTHER_TRANSPORTATION_SERVICES';

    public const RIDESHARING = 'RIDESHARING';

    // Travel Services
    public const TRAVEL_SERVICES = 'TRAVEL_SERVICES';

    // Vehicles
    public const AUTOMOTIVE_PARTS_ACCESSORIES = 'AUTOMOTIVE_PARTS_ACCESSORIES';

    public const CAR_TRUCK_COMPANIES = 'CAR_TRUCK_COMPANIES';

    public const AUTOMOTIVE_SERVICES = 'AUTOMOTIVE_SERVICES';

    public const BICYCLE_PARTS_SHOPS_SERVICE = 'BICYCLE_PARTS_SHOPS_SERVICE';

    public const CAR_BOAT_CAMPER_MOBILE_HOME_DEALER = 'CAR_BOAT_CAMPER_MOBILE_HOME_DEALER';

    public const CAR_RENTALS = 'CAR_RENTALS';

    public const MOTORCYCLE_PARTS_SHOPS_AND_DEALERS = 'MOTORCYCLE_PARTS_SHOPS_AND_DEALERS';
}
