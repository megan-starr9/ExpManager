jQuery(document).ready(function($) {

	$(".submitexp").click(function() {
		display_submission_form();
	})

	$(".expsubmit_button").click(function() {
		var categories = $("#expdialog select[name=sub_catid\\[\\]]").val();
		var thread = $("#expdialog input:hidden[name=sub_tid]").val();
		var notes = $("#expdialog textarea[name=sub_notes]").val();

		if(categories && thread) {
			$.ajax({
				url: "xmlhttp.php",
				data: {
					action : 'submitexp',
					sub_catid: JSON.stringify(categories),
					sub_tid : thread,
					sub_notes : notes
				},
				type: "post",
				dataType: 'html',
				success: function(response){
					alert("EXP Submission Complete");
					$("#expdialog").dialog("close");
					$("#expdialog textarea[name=sub_notes]").val('');
					location.reload();
				},
				error: function(response) {
					alert("There was an error "+response.responseText);
				}
			});
		}
	});

	$(".markexpawarded").click(function() {
		display_markingawarded_form();
	})

	$(".expmark_button").click(function() {
		var categories = $("#expdialog2 select[name=sub_catid\\[\\]]").val();
		var thread = $("#expdialog2 input:hidden[name=sub_tid]").val();
		var notes = $("#expdialog2 textarea[name=sub_notes]").val();

		if(categories && thread) {
			$.ajax({
				url: "xmlhttp.php",
				data: {
					action : 'markexp',
					sub_catid: JSON.stringify(categories),
					sub_tid : thread,
					sub_notes : notes
				},
				type: "post",
				dataType: 'html',
				success: function(response){
					alert("EXP Marked as Awarded");
					$("#expdialog2").dialog("close");
					$("#expdialog2 textarea[name=sub_notes]").val('');
					location.reload();
				},
				error: function(response) {
					alert("There was an error "+response.responseText);
				}
			});
		}
	});

	$(".expapprove_button").click(function() {

		var submission = $(this).attr('id');

		if(submission) {
			$.ajax({
				url: "xmlhttp.php",
				data: {
					action : 'approveexp',
					subid : submission
				},
				type: "post",
				dataType: 'html',
				success: function(response){
					alert("Thread Approved!");
					location.reload();
				},
				error: function(response) {
					alert("There was an error "+response.responseText);
				}
			});
		}
	});

$(".expapprove_button_deny").click(function() {

		var submission = $(this).attr('id');

		if(submission) {
			$.ajax({
				url: "xmlhttp.php",
				data: {
					action : 'denyexp',
					subid : submission
				},
				type: "post",
				dataType: 'html',
				success: function(response){
					alert("Thread Denied!");
					location.reload();
				},
				error: function(response) {
					alert("There was an error "+response.responseText);
				}
			});
		}
	});

	$(".expfinalize_button").click(function() {

		var category = $(this).attr('id');

		var submissions = $("input:checkbox[name=submit_cat"+category+"\\[\\]]:checked").map(function(){
		      return $(this).val();
		    }).get();

		if(category && submissions) {
			$.ajax({
				url: "xmlhttp.php",
				data: {
					action : 'finalizeexp',
					sub_catid : category,
					uid: $.urlParam('uid'),
					subids : submissions
				},
				type: "post",
				dataType: 'html',
				success: function(response){
					alert("EXP Awarded!");
					location.reload();
				},
				error: function(response) {
					alert("There was an error "+response.responseText);
				}
			});
		}
	});

	$(".expfinalize_button_deny").click(function() {

		var category = $(this).attr('id');

		var submissions = $("input:checkbox[name=submit_cat"+category+"\\[\\]]:checked").map(function(){
		      return $(this).val();
		    }).get();

		if(category && submissions) {
			$.ajax({
				url: "xmlhttp.php",
				data: {
					action : 'denyexp',
					uid: $.urlParam('uid'),
					subids : submissions
				},
				type: "post",
				dataType: 'html',
				success: function(response){
					alert("EXP Denied!");
					location.reload();
				},
				error: function(response) {
					alert("There was an error "+response.responseText);
				}
			});
		}
	});

	$(".exprequestmoderation_button").click(function() {

		var user = $(this).attr('id');

		if(user) {
			$.ajax({
				url: "xmlhttp.php",
				data: {
					action : 'requestexpmod',
					userid : user
				},
				type: "post",
				dataType: 'html',
				success: function(response){
					alert("Moderation Request Sent!");
					location.reload();
				},
				error: function(response) {
					alert("There was an error "+response.responseText);
				}
			});
		}
	});

	$(".exprequestmoderation_button_cancel").click(function() {

		var user = $(this).attr('id');

		if(user) {
			$.ajax({
				url: "xmlhttp.php",
				data: {
					action : 'requestexpmod_cancel',
					userid : user
				},
				type: "post",
				dataType: 'html',
				success: function(response){
					alert("Moderation Request Cancelled");
					location.reload();
				},
				error: function(response) {
					alert("There was an error "+response.responseText);
				}
			});
		}
	});

});

function display_submission_form() {
	$("#expdialog").dialog({dialogClass: 'modal'});
}
function display_markingawarded_form() {
	$("#expdialog2").dialog({dialogClass: 'modal'});
}

$('.expmanager_editonclick').dblclick(function() {
	$(this).attr('contenteditable', 'true');
})
$('.expmanager_editonclick').focusout(function() {
	$(this).attr('contenteditable', 'false');
	var id = $(this).attr('id');
	var type = $(this).attr('name');
	var value = $(this).text();
	if(id && type && value) {
		$.ajax({
			url: "xmlhttp.php",
			data: {
				action : 'expeditingonclick',
				element_type: type,
				element_id : id,
				element_value : value
			},
			type: "post",
			dataType: 'html',
			success: function(response){
				console.log(type+' value saved!')
			},
			error: function(response) {
				alert("There was an error "+response.responseText);
			}
		});
	}
})

$.urlParam = function(name){
    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
    if (results==null){
       return null;
    }
    else{
       return results[1] || 0;
    }
}
