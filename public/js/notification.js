
var baseURL = window.location.origin;
var userId = $('#bellBtn').val();
console.log(userId);

function checkNotification() {
    console.log(userId);
    // alert('hai');
    $('#notiList').empty();
    $('.notiCount').html();
    $.ajax({
        url: baseURL + '/listNotification',
        type: 'GET',
        success: function (response) {
            console.log(response);
            notifications = response.notifications;

            if (response.success) {
                notifications.forEach(notification => {
                    data = notification.data;
                    console.log(notification.id);
                    row = '<a href="#" class="text-reset notification-item redirect" data-notificationId="' + notification.id + '">';
                    row += '<div class="media">';
                    row += '<div class="avatar-xs me-3">'
                    row += '<span class="avatar-title ' + data['color'] + ' rounded-circle font-size-16"><i class="' + data['icon'] + '"></i></span>'
                    row += '</div>'
                    row += '<div class="media-body">'
                    row += '<h6 class="mt-0 mb-1" key="t-your-order">' + data['title'] + '</h6>'
                    row += '<div class="font-size-12 text-muted">'
                    row += '<p class="mb-1" key="t-grammer">' + data['message'] + '</p>'
                    row += '<p class="mb-0"><i class="mdi mdi-clock-outline"></i> <span key="t-min-ago">' + notification.created_at_hr + '<span></p>'
                    row += '</div></div></div></a>'

                    $('#notiList').append(row);

                });
            }
        },
        error: function (error) {
            console.log(error);
        }
    })
}

$(".refreshNoti").on('click', function () {
    checkNotification();
});

$("#notiList").on('click', '.redirect', function () {
    var notificationId = $(this).attr('data-notificationId');
    console.log(notificationId);
    window.location.href = baseURL + '/notification/read/' + notificationId;
});

$("#notiList").on('click', '.markReadAll', function () {

    $.ajax({
        url: baseURL + '/notification/read/all',
        type: "GET",
        data: {
            officer_id: officerId
        },
        success: function (response) {
            checkNotification();
        },
        error: function (error) {
            console.log(error);
        }
    });
});

