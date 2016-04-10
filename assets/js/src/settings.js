(function($) {
	var $enableInMemoryCache = $( document.getElementById( 'sc_enable_in_memory_object_caching' ) );
	var inMemoryCacheFields = document.querySelectorAll( '.in-memory-cache' );

	$enableInMemoryCache.on(
		'change', function(event) {
			if ('1' === event.target.value) {
				inMemoryCacheFields[0].className = inMemoryCacheFields[0].className.replace( /show/i, '' ) + ' show';
				inMemoryCacheFields[1].className = inMemoryCacheFields[1].className.replace( /show/i, '' ) + ' show';
			} else {
				inMemoryCacheFields[0].className = inMemoryCacheFields[0].className.replace( /show/i, '' );
				inMemoryCacheFields[1].className = inMemoryCacheFields[1].className.replace( /show/i, '' );
			}
		}
	);

	var $advancedModeToggle = $( document.getElementById( 'sc_advanced_mode' ) );
	var advancedTable = document.querySelectorAll( '.sc-advanced-mode-table' )[0];
	var simpleTable = document.querySelectorAll( '.sc-simple-mode-table' )[0];
	var pageCachingSimple = document.getElementById( 'sc_enable_page_caching_simple' );
	var pageCachingAdvanced = document.getElementById( 'sc_enable_page_caching_advanced' );

	$advancedModeToggle.on(
		'change', function(event) {
			if ('1' === event.target.value) {
				advancedTable.className = advancedTable.className.replace( /show/i, '' ) + ' show';
				simpleTable.className = simpleTable.className.replace( /show/i, '' );

				pageCachingSimple.disabled = true;
				pageCachingAdvanced.disabled = false;
			} else {
				simpleTable.className = simpleTable.className.replace( /show/i, '' ) + ' show';
				advancedTable.className = advancedTable.className.replace( /show/i, '' );

				pageCachingSimple.disabled = false;
				pageCachingAdvanced.disabled = true;
			}
		}
	);
})(jQuery);
