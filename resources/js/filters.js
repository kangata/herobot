import dayjs from "dayjs";
import relativeTime from "dayjs/plugin/relativeTime";
import localizedFormat from "dayjs/plugin/localizedFormat";

dayjs.extend(localizedFormat)
dayjs.extend(relativeTime)

// Define the formatDate function
export const formatDate = (value) => {
    return dayjs(value).format('LLL')
};

export const relativeDate = (value) => {
    return dayjs(value).fromNow()
};

export function formatPhoneNumber(phoneNumber) {
    if (!phoneNumber) return '';
    
    // Remove all non-digit characters
    const cleaned = phoneNumber.replace(/\D/g, '');
    
    // Format the number based on its length
    if (cleaned.length === 10) {
        // Format for 10-digit US numbers: (XXX) XXX-XXXX
        return `(${cleaned.slice(0, 3)}) ${cleaned.slice(3, 6)}-${cleaned.slice(6)}`;
    } else if (cleaned.length === 11 && cleaned.startsWith('1')) {
        // Format for 11-digit US numbers with country code: +1 (XXX) XXX-XXXX
        return `+1 (${cleaned.slice(1, 4)}) ${cleaned.slice(4, 7)}-${cleaned.slice(7)}`;
    } else if (cleaned.length > 12 && cleaned.startsWith('62')) {
        // Format for Indonesian numbers with country code: +62 (XXX) XXX-XXXX
        return `+62 ${cleaned.slice(2, 5)}-${cleaned.slice(5, 9)}-${cleaned.slice(9)}`;
    } else {
        // For other formats, just add a '+' at the beginning if it's not there
        return phoneNumber.startsWith('+') ? phoneNumber : `+${phoneNumber}`;
    }
}