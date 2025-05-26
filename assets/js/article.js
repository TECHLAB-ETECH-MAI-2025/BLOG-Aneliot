import $ from 'jquery';

$(document).ready(function() {
    const $commentForm = $('#comment-form');
    const $commentsList = $('#comments-list');
    const $commentsCount = $('#comments-count');
    const $commentModal = $('#commentModal' + $commentForm.data('article-id'));
    const $alertsContainer = $('#alerts-container');

    $commentForm.on('submit', function (e) {
        e.preventDefault();

        const $submitBtn = $commentForm.find('button[type="submit"]');
        const originalBtnText = $submitBtn.html();

        $submitBtn.html('Envoi en cours...').prop('disabled', true);

        $.ajax({
            url: $commentForm.attr('action'),
            method: 'POST',
            data: $commentForm.serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $commentsList.prepend(response.commentHtml);
                    $commentsCount.text(response.commentsCount);
                    $commentForm[0].reset();

                    showAlert('success', 'Votre commentaire a été publié avec succès !');

                    closeModal($commentModal);

                } else {
                    showAlert('danger', response.error || 'Une erreur est survenue.');
                }
            },
            error: function () {
                showAlert('danger', 'Une erreur est survenue lors de l\'envoi.');
            },
            complete: function () {
                $submitBtn.html(originalBtnText).prop('disabled', false);
            }
        });
    });

    // Système de "j'aime" en AJAX
    $('.like-button').on('click', function() {
        const $btn = $(this);
        const articleId = $btn.data('article-id');

        $.ajax({
            url: `/article/${articleId}/like`,
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Toggle 'liked' class based on response
                    if (response.liked) {
                        $btn.addClass('liked');
                        $btn.text('❤️ Vous aimez');
                    } else {
                        $btn.removeClass('liked');
                        $btn.text('❤️ J\'aime');
                    }

                    // Update likes count
                    $('#likes-count').text(response.likesCount);
                }
            },
            error: function() {
                alert('Erreur lors de l\'envoi de la requête.');
            }
        });
    });

    function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    `;

    $('#alerts-container').html(alertHtml);

    // Auto close after 5 seconds
    setTimeout(() => {
        $('#alerts-container .alert').alert('close');
    }, 5000);

}
function closeModal($modal) {
  // Hide the modal itself
  $modal.removeClass('show').css('display', 'none').attr({
    'aria-hidden': 'true',
    'aria-modal': 'false',
    'role': ''
  });

  // Remove the backdrop
  $('.modal-backdrop').remove();

  // Remove 'modal-open' class and reset body's padding if added
  $('body').removeClass('modal-open').css({
    'overflow': '',
    'padding-right': ''
  });

  // If there's any focus lock or trap, blur the active element
  if (document.activeElement) {
    document.activeElement.blur();
  }
}

});