//ANIMATING SCREEN LOADING

$(window).load(function() {

	$(".fadeOverlay").velocity({opacity:0}, 1000);

});

//ANIMATING SCREEN ON CLICK LINK

$('.animatedLink').click(function(event){
  event.preventDefault();

  var link = $(this).attr('href');
  $('.fadeOverlay').velocity({opacity: 1},{display:'block'},{position:'absolute'},500);
  $('.menu').velocity({opacity: 0},{display:'none'},500);
  $('footer').velocity({opacity: 0},{display:'none'},500);

    	$('.logoOverlay').velocity({opacity:1},{duration:1500, 
    		complete:function(){
    			$('.logoOverlay').velocity({opacity:0},{duration:1500, complete: function(){window.location = link;}});
 	 			
 			}
		});
  
  			

});

