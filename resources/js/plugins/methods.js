export default {
    formatFloatNumber: function (number) {
        if (isNaN(number)) {
            return 0.00;
        }
        return new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(number).replace(',', '.');
    },
    formatStringLength: function (string, length) {
        return string.substring(0, length)
    }
}