$(document).on('click', '.userProfile', function(){

    var userId = $(this).attr('data-userId');
    var data = {user_id: userId};

    $(".userProfileContent").html("");

    var BaseURL = window.location.origin;
    $.ajax({
        url: BaseURL + "/api/get-user-profile",
        type: "POST",
        data: data,
        success:function(response){
            if(response.success){
                var userProfile = response.userProfile;

                for (var attribute in userProfile) {
                    if (attribute == 'profile_picture') {
                        $('#userProfile-'+attribute).attr('src', userProfile[attribute]);
                    } else {
                        $('#userProfile-'+attribute).html(userProfile[attribute]);
                    }
                }

                $('#userProfile').modal('show');
            }
        },
        error: function(response){
            console.log(response);
        }
    });
});

// });
