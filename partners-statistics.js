let inputStartDate = moment()
let inputEndDate = moment()

jQuery(document).ready(function($) {
    let timeStart = $('.time__start');
    let timeEnd = $('.time__end');
    $(".type-today").click(function(event) {
        timeStart.html(moment().format('YYYY-MM-DD'))
        timeEnd.html(moment().format('YYYY-MM-DD'))
        $('#statistics-date_type').val('today')
        $('#statistics-event_date').val(moment().format('YYYY-MM-DD') + ' - ' + moment().format('YYYY-MM-DD'))
        $(this).addClass('date__button_active')
        $('.type-7days').removeClass('date__button_active')
        $('.type-1month').removeClass('date__button_active')
    });
    $(".type-7days").click(function(event) {
        timeStart.html(moment().subtract(6, 'days').format('YYYY-MM-DD'))
        timeEnd.html(moment().format('YYYY-MM-DD'))
        $('#statistics-date_type').val('7days')
        $('#statistics-event_date').val(moment().subtract(6, 'days').format('YYYY-MM-DD') + ' - ' + moment().format('YYYY-MM-DD'))
        $(this).addClass('date__button_active')
        $('.type-today').removeClass('date__button_active')
        $('.type-1month').removeClass('date__button_active')
    });
    $(".type-1month").click(function(event) {
        timeStart.html(moment().startOf('month').format('YYYY-MM-DD'))
        timeEnd.html(moment().endOf('month').format('YYYY-MM-DD'))
        $('#statistics-date_type').val('1month')
        $('#statistics-event_date').val(moment().startOf('month').format('YYYY-MM-DD') + ' - ' + moment().endOf('month').format('YYYY-MM-DD'))
        $(this).addClass('date__button_active')
        $('.type-today').removeClass('date__button_active')
        $('.type-7days').removeClass('date__button_active')
    });
})

$(function() {
    let input = $('.date__right');
    let timeStart = $('.time__start');
    let timeEnd = $('.time__end');
    let inputValue = timeStart.text() + ' - ' + timeStart.text();

    let options = {
        startDate: inputStartDate,
        endDate: inputEndDate,
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear',
            format: 'YYYY-MM-DD'
        },
        ranges: {
           'Today': [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }

    if(inputValue) {
        const {startDate, endDate} = getSplit(inputValue);
        options.startDate = startDate;
        options.endDate = endDate;
    }

    input.daterangepicker(options);

    $(input).on('apply.daterangepicker', function(ev, picker) {
        timeStart.html(picker.startDate.format('YYYY-MM-DD'))
        timeEnd.html(picker.endDate.format('YYYY-MM-DD'))
        $('#statistics-date_type').val('custom')
        $('#statistics-event_date').val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'))
        $('.type-today').removeClass('date__button_active')
        $('.type-7days').removeClass('date__button_active')
        $('.type-1month').removeClass('date__button_active')
    });
});

function getSplit(date) {
    let strings = date.split(' - ');
    return {
        startDate: strings[0],
        endDate: strings[1],
    }
}