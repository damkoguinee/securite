document.addEventListener('DOMContentLoaded', function () {
    ClassicEditor
        .create( document.querySelector( '#editor' ), {
            // toolbar: ['heading', 'bold', 'italic']
        } )        
        .catch( error => {
            console.error( error );
        } )

        .then(editor => {
            document.querySelector("#ajoutForm form").addEventListener("submit", function(e){
                e.preventDefault();
                this.querySelector("#editor ~ input").value = editor.getData();
                this.submit();
                
            })

        })
    
});