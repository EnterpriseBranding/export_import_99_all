(function($) {
    $(document).ready(function() {

    if ($('#export_import_globals').length>0)
    {
        $(document).on('change','#export_import_globals',function()
        {
            var id=$(this).val();
            $('.fill_in').each(function(){
                var name=$(this).attr('name')
                if ($('#'+id).attr('data-'+name))
                {
                    $(this).val($('#'+id).attr('data-'+name));
                }
                else
                {
                    $(this).val('');
                }
            })
        });
    }

    });

})(jQuery);
