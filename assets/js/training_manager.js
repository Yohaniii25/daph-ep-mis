/**
 * Operational Trainings Shared Manager
 * Handles Training Logging, File Upload Previews, Modals, and DataTables for:
 * 1. Projects and Monitoring (projects_progress.php)
 * 2. Dairy Hub (dairy_hub.php)
 */

var TrainingManager = (function() {
    'use strict';

    function initImagePreview() {
        $('#tr_photo').on('change', function(e) {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(evt) {
                    $('#tr_photo_preview_img').attr('src', evt.target.result);
                    $('#tr_photo_label').text(file.name);
                    $('#tr_photo_sub').text((file.size / 1024).toFixed(1) + ' KB - Ready to upload');
                    $('#tr_photo_preview_container').removeClass('d-none');
                };
                reader.readAsDataURL(file);
            }
        });

        $('#tr_btn_clear_photo').on('click', function() {
            $('#tr_photo').val('');
            $('#tr_photo_preview_container').addClass('d-none');
            $('#tr_photo_preview_img').attr('src', '');
        });
    }

    function initFormSubmit() {
        $('#trainingSessionForm').on('submit', function(e) {
            e.preventDefault();
            var form = this;

            if (!form.checkValidity()) {
                e.stopPropagation();
                $(form).addClass('was-validated');
                return;
            }

            var formData = new FormData(form);
            var $submitBtn = $('#tr_btn_submit');
            var originalText = $submitBtn.html();

            $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

            $.ajax({
                url: 'processors/training_crud.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    $submitBtn.prop('disabled', false).html(originalText);
                    if (res && res.success) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: res.message,
                                timer: 1800,
                                showConfirmButton: false
                            }).then(function() {
                                var url = new URL(window.location.href);
                                url.searchParams.set('tab', 'trainings');
                                window.location.href = url.toString();
                            });
                        } else {
                            alert(res.message);
                            var url = new URL(window.location.href);
                            url.searchParams.set('tab', 'trainings');
                            window.location.href = url.toString();
                        }
                    } else {
                        var err = res && res.message ? res.message : 'An error occurred while saving the training session.';
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'error', title: 'Error', text: err });
                        } else {
                            alert('Error: ' + err);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    $submitBtn.prop('disabled', false).html(originalText);
                    var msg = 'Failed to process request: ' + error;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Network Error', text: msg });
                    } else {
                        alert(msg);
                    }
                }
            });
        });
    }

    function openCreateModal(context, contextTitle) {
        var form = document.getElementById('trainingSessionForm');
        if (form) {
            form.reset();
            $(form).removeClass('was-validated');
        }

        $('#tr_training_id').val('0');
        $('#tr_module_context').val(context || 'projects');
        $('#modalLogTrainingLabel').html('<i class="bi bi-mortarboard-fill me-2"></i>Log ' + (contextTitle || 'Training Session'));
        $('#modalTrainingSubtitle').text('Record operational training, attendance count, and field photo evidence.');
        $('#tr_submit_text').text('Save Training Record');
        $('#tr_photo_preview_container').addClass('d-none');
        $('#tr_photo_preview_img').attr('src', '');

        // Set default date to today
        var today = new Date().toISOString().split('T')[0];
        $('#tr_training_date').val(today);

        var modalEl = document.getElementById('modalLogTraining');
        if (modalEl) {
            var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        }
    }

    function editTraining(id) {
        if (!id) return;

        $.ajax({
            url: 'processors/training_crud.php',
            type: 'GET',
            data: { action: 'get_training', id: id },
            dataType: 'json',
            success: function(res) {
                if (res && res.success && res.data) {
                    var d = res.data;
                    $('#tr_training_id').val(d.id);
                    $('#tr_module_context').val(d.module_context);
                    $('#tr_range_id').val(d.range_id);
                    $('#tr_training_date').val(d.training_date);
                    $('#tr_topic').val(d.topic);
                    $('#tr_target_group').val(d.target_group);
                    $('#tr_location').val(d.location);
                    $('#tr_attendance').val(d.attendance);
                    $('#tr_trainer_name').val(d.trainer_name || '');
                    $('#tr_remarks').val(d.remarks || '');

                    $('#modalLogTrainingLabel').html('<i class="bi bi-pencil-square me-2"></i>Edit Training Session #' + d.id);
                    $('#tr_submit_text').text('Update Training Record');

                    if (d.photo_path) {
                        var photoUrl = '../../../../' + d.photo_path.replace(/^\/+/, '');
                        $('#tr_photo_preview_img').attr('src', photoUrl);
                        $('#tr_photo_label').text('Existing Session Photo');
                        $('#tr_photo_sub').text('Choose new file below to replace');
                        $('#tr_photo_preview_container').removeClass('d-none');
                    } else {
                        $('#tr_photo_preview_container').addClass('d-none');
                    }

                    var modalEl = document.getElementById('modalLogTraining');
                    if (modalEl) {
                        var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        modal.show();
                    }
                } else {
                    alert('Could not load training details: ' + (res.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('Server error while loading training session details.');
            }
        });
    }

    function deleteTraining(id) {
        if (!id) return;

        var doDelete = function() {
            $.ajax({
                url: 'processors/training_crud.php',
                type: 'POST',
                data: { action: 'delete_training', id: id },
                dataType: 'json',
                success: function(res) {
                    if (res && res.success) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            alert(res.message);
                            location.reload();
                        }
                    } else {
                        alert('Error: ' + (res.message || 'Could not delete record'));
                    }
                },
                error: function() {
                    alert('Server error while deleting training record.');
                }
            });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Delete Training Record?',
                text: 'This action cannot be undone and will permanently remove this training log and its attached photo.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete'
            }).then(function(result) {
                if (result.isConfirmed) {
                    doDelete();
                }
            });
        } else {
            if (confirm('Are you sure you want to delete this training record?')) {
                doDelete();
            }
        }
    }

    function viewPhoto(src, caption) {
        $('#viewPhotoImg').attr('src', src);
        $('#viewPhotoCaption').text(caption || '');
        var modalEl = document.getElementById('modalViewTrainingPhoto');
        if (modalEl) {
            var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        }
    }

    return {
        init: function() {
            initImagePreview();
            initFormSubmit();
        },
        openCreateModal: openCreateModal,
        editTraining: editTraining,
        deleteTraining: deleteTraining,
        viewPhoto: viewPhoto
    };
})();

$(document).ready(function() {
    TrainingManager.init();
});
