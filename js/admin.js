console.log('Maxim Maeder');

console.log({ document });
console.log(this);
console.log(ajaxurl);

let obj = { action: 'get_time' };

const data = new FormData();

data.append('action', 'get_time');
data.append('nonce', PHPVARS.nonce);

fetch(ajaxurl, {
    method: 'POST', // or 'PUT'
    credentials: 'same-origin',
    body: data,
})
.then((re) => re.json())
.then((json) => console.log(json))
