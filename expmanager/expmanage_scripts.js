jQuery(document).ready(function($) {
	
	$(".submitexp").click(function() {
		display_submission_form();
	})
	
	$(".expsubmit_button").click(function() {
		
		var category = $("select[name=sub_catid] option:selected").val();
		var thread = $("input:hidden[name=sub_tid]").val();
		var notes = $("textarea[name=sub_notes]").val();

		if(category && thread) {
			$.ajax({
				url: "xmlhttp.php",
				data: {
					action : 'submitexp',
					sub_catid: category,
					sub_tid : thread,
					sub_notes : notes
				},
				type: "post",
				dataType: 'html',
				success: function(response){
					alert("EXP Submission Complete");
					$("#expdialog").dialog("close");
					$("textarea[name=sub_notes]").val('');
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
	
});

function display_submission_form() {
	$( "#expdialog" ).dialog({dialogClass: 'modal'});
}

$.urlParam = function(name){
    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
    if (results==null){
       return null;
    }
    else{
       return results[1] || 0;
    }
}