function readFile(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();

        reader.onload = function (e) {

            var htmlPreview = '<ul class="list-group list-group-lg list-group-flush">'
                + '<li class="list-group-item px-0">'
                + '         <div class="row align-items-center">'
                + '            <div class="col-auto">'
                + '                 <div class="avatar">'
                + '                    <img class="avatar-img rounded" src="' + e.target.result + '" alt="...">'
                + '                </div>'
                + '             </div>'
                + '            <div class="col ml--3">'
                + '                <h4 class="mb-1">' + input.files[0].name + '</h4>'
                + '                <p class="small text-muted mb-0">23 KB</p>'
                + '            </div>'
                + '            <div class="col-auto">'
                + '                <div class="dropdown">'
                + '                    <a href="#" class="dropdown-ellipses dropdown-toggle" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
                + '                        <i class="fe fe-more-vertical"></i>'
                + '                    </a>'
                + '                    <div class="dropdown-menu dropdown-menu-right">'
                + '                        <a href="#" class="dropdown-item">'
                + '                            Remove'
                + '                        </a>'
                + '                    </div>'
                + '                </div>'
                + '            </div>'
                + '        </div>'
                + '    </li>'
                + ' </ul>'
                
            var wrapperZone = $(input).parent();
            var previewZone = $(input).parent().parent().find('.preview-zone');
            var boxZone = $(input).parent().parent().find('.preview-zone').find('.box').find('.box-body');

            wrapperZone.removeClass('dragover');
            previewZone.removeClass('hidden');
            boxZone.empty();
            boxZone.append(htmlPreview);
        };

        reader.readAsDataURL(input.files[0]);
    }
}

function reset(e) {
    e.wrap('<form>').closest('form').get(0).reset();
    e.unwrap();
}
$(".dropzone").change(function () {
    readFile(this);
});
$('.dropzone-wrapper').on('dragover', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).addClass('dragover');
});
$('.dropzone-wrapper').on('dragleave', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass('dragover');
});
$('.remove-preview').on('click', function () {
    var boxZone = $(this).parents('.preview-zone').find('.box-body');
    var previewZone = $(this).parents('.preview-zone');
    var dropzone = $(this).parents('.form-group').find('.dropzone');
    boxZone.empty();
    previewZone.addClass('hidden');
    reset(dropzone);
});