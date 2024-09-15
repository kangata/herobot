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