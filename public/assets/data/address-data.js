/**
 * Address dropdown data shared by Teacher and Student registration forms.
 *
 * COUNTRIES: the fixed country list from the spec.
 * PROVINCES_BY_COUNTRY: top-level administrative divisions (state/province/
 * division) per country, used to populate the Province/State dropdown once
 * a Country is chosen. Nepal reuses NEPAL_PROVINCES (see nepal-address.js)
 * which additionally cascades down to all 77 districts; for the other six
 * named countries the District field stays a free-text input (a full
 * district-level dataset for six countries is out of scope here), and for
 * "Other" both Province and District are free text.
 */
const COUNTRIES = ['Nepal', 'India', 'China', 'Bhutan', 'Bangladesh', 'Pakistan', 'Sri Lanka', 'Other'];

const PROVINCES_BY_COUNTRY = {
    'India': [
        'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat',
        'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh',
        'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
        'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh',
        'Uttarakhand', 'West Bengal', 'Delhi (NCT)', 'Jammu and Kashmir', 'Ladakh',
        'Puducherry', 'Chandigarh', 'Other',
    ],
    'China': [
        'Anhui', 'Beijing', 'Chongqing', 'Fujian', 'Gansu', 'Guangdong', 'Guangxi', 'Guizhou',
        'Hainan', 'Hebei', 'Heilongjiang', 'Henan', 'Hong Kong', 'Hubei', 'Hunan', 'Inner Mongolia',
        'Jiangsu', 'Jiangxi', 'Jilin', 'Liaoning', 'Macau', 'Ningxia', 'Qinghai', 'Shaanxi',
        'Shandong', 'Shanghai', 'Shanxi', 'Sichuan', 'Tianjin', 'Tibet', 'Xinjiang', 'Yunnan',
        'Zhejiang', 'Other',
    ],
    'Bhutan': [
        'Bumthang', 'Chukha', 'Dagana', 'Gasa', 'Haa', 'Lhuentse', 'Mongar', 'Paro', 'Pemagatshel',
        'Punakha', 'Samdrup Jongkhar', 'Samtse', 'Sarpang', 'Thimphu', 'Trashigang',
        'Trashiyangtse', 'Trongsa', 'Tsirang', 'Wangdue Phodrang', 'Zhemgang', 'Other',
    ],
    'Bangladesh': [
        'Barishal', 'Chattogram', 'Dhaka', 'Khulna', 'Mymensingh', 'Rajshahi', 'Rangpur', 'Sylhet', 'Other',
    ],
    'Pakistan': [
        'Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan', 'Gilgit-Baltistan',
        'Azad Jammu and Kashmir', 'Islamabad Capital Territory', 'Other',
    ],
    'Sri Lanka': [
        'Central', 'Eastern', 'North Central', 'Northern', 'North Western', 'Sabaragamuwa',
        'Southern', 'Uva', 'Western', 'Other',
    ],
};

/** Nationality dropdown -> matching phone country code (for the phone_country_code auto-fill). */
const NATIONALITY_PHONE_CODES = {
    'Nepali': '+977', 'Indian': '+91', 'Chinese': '+86', 'Bhutanese': '+975',
    'Bangladeshi': '+880', 'Pakistani': '+92', 'Sri Lankan': '+94', 'Other': '',
};
