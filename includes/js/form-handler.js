export async function sendFormData(form) {
    const url = form.action;
    const method = form.method;

    // Convert FormData to JSON
    var object = {};
    new FormData(form).forEach((value, key) => {
        if (key in object === false) {
            object[key] = value;
            return;
        }
        if (Array.isArray(object[key]) === false) {
            object[key] = [object[key]];
        }
        object[key].push(value);
    });
    const payload = JSON.stringify(object);

    // Execute request
    return await fetch(url, {
        method: method,
        body: payload,
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json'
        }
    }).then(function(response) {
        return response.json();
    }).then(function(data) {
        return data;    
    }).catch(function(error) {
        console.error(error);
        return {error: {code: null, message: error.message}, success: false};
    });
};