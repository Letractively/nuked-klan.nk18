$(document).ready(function() {
	$('.nkPopupBox').each(function() {
		$(this).click(function(e) {
			e.preventDefault();
			popupBox($(this).attr('href'));
		});	
	});
})

function popupBox(url){
	// Verification qu'il n'y a pas une popup déjà ouverte
	if($('.popupBox').length > 0){
		// Suppression des popups déjà ouverte
		popupBoxRefresh(url);
	}
	else{
		// Création des éléments HTML
		var popupBox = $(document.createElement('div'));
		var popupBoxWrapper = $(document.createElement('div'));
		var popupBoxContent = $(document.createElement('div'));
		// Ajout des CSS
		popupBox.addClass('popupBox');
		popupBoxWrapper.addClass('popupBoxWrapper');
		popupBoxContent.addClass('popupBoxContent');
		// Insertion dans le DOM
		popupBox.appendTo('body');
		popupBoxWrapper.append(popupBoxContent).appendTo('body');
		// Ajout de la gestion de la fermeture
		popupBox.click(function(){
			// Appel de la fonction de fermeture
			popupBoxClose();				
		});
		popupBox.slideDown(400, function(){
			popupBoxWrapper.fadeIn(function(){
				popupBoxAjax(popupBoxContent, url);
			});
		});
	}

}


function popupBoxRefresh(urlContent){
	$('.popupBoxContent').fadeOut(500, function(){
		popupBoxAjax($(this), urlContent);
	})
}


function popupBoxClose(){
	// Animation de disparition du contenu de la popup
	$('.popupBoxWrapper').each(function(){
		$(this).fadeOut(500, function(){
			// Animation de slide du background
			$(this).remove();
			$('.popupBox').each(function(){
				$(this).slideUp(500, function(){
					// Suppression de la popup dans le DOM
					$(this).remove();
				});
			})
		});
	});
}


function popupBoxAjax(element, urlContent, data){
	$.ajax({
        async: true,
        type: 'POST',
        url: urlContent,
        data: data,
        success:function(data){
        	// Inclusion du retour Ajax dans l'element passer en paramètre
        	element.html(data);
        },
        complete:function(){
        	popupContent = element;
        	popupWrapper = element.parent();
        	// Positionnement de la popup au centre de la fenêtre
        		// Récupération des dimensions de la fenetre
        	bodyWidth = $(window).width();
        	bodyHeight = $(window).height();

        		// On determine les dimensions maximum de la popup
        	maxWidth = bodyWidth - 130;
        	maxHeight = bodyHeight - 70;
        		// On récupère les dimensions du contenu
        	contentWidth = popupContent.outerWidth(true);
        	contentHeight = popupContent.outerHeight(true);

        	if(contentWidth > maxWidth){
        		contentWidth = maxWidth;
        		popupWrapper.animate({'width': maxWidth - 30}, 400);
        	}else{
        		popupWrapper.animate({'width': contentWidth - 30}, 400);
        	}
        	if(contentHeight > maxHeight){
        		contentHeight = maxHeight;
        		popupWrapper.animate({'height': maxHeight - 30}, 400);
        	}
        	else{
        		popupWrapper.animate({'height': contentHeight - 30}, 400);
        	}
        	
        	popupContent.animate({'height': contentHeight - 30}, 400);

        	posY = (bodyHeight - contentHeight) / 2;
        	posX = (bodyWidth - contentWidth) / 2;
        	popupWrapper.css({'top': posY, 'left' : posX});
        	element.fadeIn();
        	// Ajout de l'event click pour les liens popupBox        	
        	element.find('.nkPopupBox').each(function() {
        		$(this).click(function(e) {
					e.preventDefault();
					popupBox($(this).attr('href'));
				});	
			});
			// Ajout de l'event submit pour les form popupBox
			element.find('.nkPopupBoxForm').each(function(){
				$(this).click(function(e){
					e.preventDefault()
					popupBoxCheckForm($(this).parent(), $(this).parent().attr('action')+'&nuked_nude=index');
				});
			});
        }
    });
}

function popupBoxCheckForm(element, urlContent){
	var data = '';
	var i = 0;
	element.children(':input').each(function(){
		if(i > 0) data += '&';
		data += $(this).attr('name')+'='+$(this).val();
		i++;
	});
	popupBoxAjax(element, urlContent, data);
}
