/**
 * JS Scripts for generating content inside a ThickBox.
 */

jQuery(document).ready(function($) {

	// Ajax loader
	$('.scg_spinner')
		.hide()
		.ajaxStop(function () {	$(this).hide();	});


	// Show ".scg_replace_b_i" row if "#scg_decorate" is checked
	if ($('#scg_decorate').attr('checked'))
		$('.scg_replace_b_i').show();
	else
		$('.scg_replace_b_i').hide();
	$('#scg_decorate').on('change', function() {
		if (this.checked)
			$('.scg_replace_b_i').fadeIn(200);
		else
			$('.scg_replace_b_i').fadeOut(200);
	});

	// Ajax Call to sample_content action
	generate_content = function() {
		$('.scg_spinner').show();
		$.post(
			scg_constants.ajaxurl,
			{
				action : 'sample_content',
				scg_number_of_paragraphs : $('#scg_number_of_paragraphs').val(),
				scg_headers : $('#scg_headers').attr('checked'),
				scg_paragraphs_length : $('#scg_paragraphs_length').val(),
				scg_img : $('#scg_img').attr('checked'),
				scg_decorate : $('#scg_decorate').attr('checked'),
				scg_replace_b_i : $('#scg_replace_b_i').attr('checked'),
				scg_links : $('#scg_links').attr('checked'),
				scg_ul : $('#scg_ul').attr('checked'),
				scg_ol : $('#scg_ol').attr('checked'),
				scg_dl : $('#scg_dl').attr('checked'),
				scg_code : $('#scg_code').attr('checked'),
				scg_bq : $('#scg_bq').attr('checked')
			},
			function(response) {
				window.send_to_editor(response);
			}
		);
	}

	$('#scg_content .generate').on('click', function(e) { generate_content(); });
	$('#scg_content .cancel').on('click', function(e) { e.preventDefault(); tb_remove(); });

});