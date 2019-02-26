jQuery(function($){
   function society() {
      let host = window.location.host.toString().toLowerCase();
      if (host.includes('aseees.')) {
         return 'ASEEES COMMONS';
      };
      if (host.includes('mla.')) {
         return 'MLA COMMONS';
      };
      if (host.includes('caa.')) {
         return 'CAA COMMONS';
      };
      if (host.includes('ajs.')) {
         return 'AJS COMMONS';
      };
      if (host.includes('up.')) {
         return 'AP COMMONS';
      };
      return 'HUMANITIES COMMONS';
   };
   
   $('.option-site-visibility').children()[1].innerHTML = '<td><label class="checkbox" for="blog_public_on">' +
          '<input type="radio" id="blog_public_on" name="blog_public" value="1" checked="checked" class="styled">' +
          '<strong>Public and allow search engines to index this site. <i style="font-weight: normal">Note: it is up to search' +
          'engines to honor your request. The site will appear in public listings around HUMANITIES COMMONS.</i></strong>' +
       '</label><br/>' +
       '<label class="checkbox" for="blog_public_off">' +
          '<input type="radio" id="blog_public_off" name="blog_public" value="0" class="styled">' +
          '<strong>Public but discourage search engines from index this site. <i style="font-weight: normal">Note: this option' +
          'does not block access to your site — it is up to search engines to honor your request. The site will appear in' +
          'public listings around HUMANITIES COMMONS</i></strong>' +
       '</label><br/>' +
       '<label class="checkbox" for="blog-private-1"><input id="blog-private-1" type="radio" name="blog_public" value="-1" class="styled"><strong>Visible only to registered users of '+ society() +'</strong></label>' +
       '<br/>' +
       '<label class="checkbox" for="blog-private-2"><input id="blog-private-2" type="radio" name="blog_public" value="-2" class="styled"><strong>Visible only to registered users of this site</strong></label>' +
       '<br/>' +
       '<label class="checkbox" for="blog-private-3"><input id="blog-private-3" type="radio" name="blog_public" value="-3" class="styled"><strong>Visible only to administrators of this site</strong></label></td>';
});