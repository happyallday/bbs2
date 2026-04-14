KindEditor.ready(function(K) {
    window.editor = K.create('#editor', {
        uploadJson: '?action=upload_file',
        width: '100%',
        height: '350px',
        items: ['source','undo','redo','bold','italic','underline','forecolor','link','image','emoticons','preview','fullscreen'],
        afterBlur: function() { this.sync(); }
    });
    
    if (document.getElementById('reply-editor')) {
        K.create('#reply-editor', {
            width: '100%',
            height: '150px',
            items: ['bold','italic','underline','forecolor','link','image','emoticons']
        });
    }
});