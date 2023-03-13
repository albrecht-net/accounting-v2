function sendFormData(formID) {
    const form = document.getElementById(formID);

    form.addEventListener('submit', function(event) {
        event.preventDefault();

        console.info("Form was submitted.");
        
        const url = this.action;
        const method = 'POST';
        const payload = new FormData(this);

        fetch(url, {
            method: method,
            body: payload,
            credentials: 'omit'
        }).then(function(response) {
            console.log(response);
            if (!response.ok) {
                throw new Error(`Looks like there was a problem. Response Error: ${response.status} ${response.statusText}`);
            } else {
                return response.json();
            }
    
        }).then(function(data) {
            console.info(data);
            form.reset();

            // window.location.replace()
    
        }).catch(function(error) {
            console.error(error);
        });

    });
}
