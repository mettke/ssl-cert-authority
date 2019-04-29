var CryptoJSAesJson = {
    stringify: function (cipherParams) {
        var j = {ct: cipherParams.ciphertext.toString(CryptoJS.enc.Base64)};
        if (cipherParams.iv) j.iv = cipherParams.iv.toString();
        if (cipherParams.salt) j.s = cipherParams.salt.toString();
        return JSON.stringify(j).replace(/\s/g, '');
    },
    parse: function (jsonStr) {
        var j = JSON.parse(jsonStr);
        var cipherParams = CryptoJS.lib.CipherParams.create({ciphertext: CryptoJS.enc.Base64.parse(j.ct)});
        if (j.iv) cipherParams.iv = CryptoJS.enc.Hex.parse(j.iv);
        if (j.s) cipherParams.salt = CryptoJS.enc.Hex.parse(j.s);
        return cipherParams;
    }
}

var rsa = new RSAKey();
rsa.setPublic('<?php out($this->get('rsa_n')) ?>', '<?php out($this->get('rsa_e')) ?>');

function dec2hex (dec) {
    return ('0' + dec.toString(16)).substr(-2)
}
  
function generateKey (len) {
    var arr = new Uint8Array((len || 40) / 2)
    window.crypto.getRandomValues(arr)
    return Array.from(arr, dec2hex).join('')
}

function do_encrypt() {
    var key = generateKey();

    var private = document.getElementById('private');
    private.value = CryptoJS.AES.encrypt(JSON.stringify(private.value), key, {format: CryptoJSAesJson}).toString()

    var password = document.getElementById('password');
    password.value = rsa.encrypt(key);
}

window.document.onload = function(e) { 
    var encrypt = document.getElementById('private');
    encrypt.onblur = do_encrypt;
}
window.onload = function(e) {  
    var encrypt = document.getElementById('private');
    encrypt.onblur = do_encrypt;
}
