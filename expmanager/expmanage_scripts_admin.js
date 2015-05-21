$(document).on('click','#create_category', function() {
  $.ajax({
    url: "../xmlhttp.php",
    data: {
      action : 'createcategory'
    },
    type: "post",
    dataType: 'html',
    success: function(response){
      location.reload();
    },
    error: function(response) {
      alert("There was an error "+response.responseText);
    }
  });
});
