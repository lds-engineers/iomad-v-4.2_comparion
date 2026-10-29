define(['jquery', 'core/log'], function($, log) {
    return {
        init: function() {
            log.debug('DataTables initialized');
            $('#usetTable').DataTable();
        }
    };
});
