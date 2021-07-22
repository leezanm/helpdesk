function resetForm(form) {
    form.find('input').val('');
    form.find('textarea').html('');
    form.find('select').val('').trigger('change');
};

function clearForm(form) {
    var elements = form.elements;

    form.reset();

    for (i = 0; i < elements.length; i++) {

        field_type = elements[i].type.toLowerCase();

        switch (field_type) {

            case "text":
            case "number":
            case "password":
                elements[i].value = "";
                break;
            case "textarea":
                // elements[i].value = "";
            case "radio":
            case "checkbox":
                if (elements[i].checked) {
                    elements[i].checked = false;
                }
                break;
            case "select-one":
            case "select-multi":
                elements[i].selectedIndex = -1;
                break;
            default:
                break;
        }
    }
}

function clearError(form) {
    form.find('input').removeClass('is-invalid');
    form.find('textarea').removeClass('is-invalid');
    form.find('select').removeClass('is-invalid');

    $('.error').addClass('collapse');
};

function errorValidate(errorResponse, form) {
    if (errorResponse.responseJSON.errors) {
        errors = errorResponse.responseJSON.errors;
        $.each(errors, function (key, error) {
            form.find('input[name="' + key + '"]').addClass('is-invalid');
            form.find('textarea[name="' + key + '"]').addClass('is-invalid');
            form.find('select[name="' + key + '"]').addClass('is-invalid');
            form.find('.error-' + key).html(error[0]);
            form.find('.error-' + key).removeClass('collapse');
        });
    }
}

function populateData(data, form) {
    for (var property in data) {
        form.find('input[name="' + property + '"]').val(data[property]);
        form.find('select[name="' + property + '"]').val(data[property]);
        form.find('select[name="' + property + '"]').trigger('change');
        form.find('textarea[name="' + property + '"]').html(data[property]);
    }
}

function ajaxCall(form, formData, callback, failCallback = 'defaultFailCallback') {
    clearError(form);
    $.ajax({
        url: form.attr('action'),
        method: form.attr('method'),
        data: formData,
        processData: false,
        contentType: false,
        success: window[callback],
        error: window[failCallback]
    });
}

var defaultFailCallback = function (errorResponse) {
    if (errorResponse.responseJSON.errors) {
        errorValidate(errorResponse, form);
    }
}
