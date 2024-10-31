/**
 * Computes the SHA-1 hash of a file selected by the user and updates the hidden input field with the computed hash value.
 */
function senpai_software_2fa_upload() {
    const fileInput = document.getElementById('senpai_software_2fa_file');

    var msg_error = document.getElementById('senpai_software_2fa_error');
    msg_error.style= "display:none;";

    // Check if a file has been selected
    if (fileInput && fileInput.files && fileInput.files[0]) {
        const file = fileInput.files[0];

        if(file.size < 1080000000) {

            // Display the name of the selected file
            const fileName = document.getElementById('senpai_software_2fa_name');
            fileName.innerHTML = "File: " + file.name;

            // Update the progress bar while computing the hash
            const progressElement = document.getElementById('senpai_software_2fa_progress');
            const reader = new FileReader();
            reader.onloadstart = function () {
                progressElement.innerHTML = 'Computing...';
            };
            reader.onprogress = function (e) {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    progressElement.innerHTML = 'Computing: ' + percentComplete + '%';
                } else {
                    progressElement.innerHTML = 'Computing...';
                }
            };
            reader.onload = async function (e) {
                const contents = e.target.result;

                // Compute the SHA-1 hash of the file contents
                const hash = await senpai_software_2fa_sha1(contents);

                // Update the hidden input field with the computed hash value
                const hiddenInput = document.getElementById('senpai_software_2fa_hash');
                hiddenInput.value = hash;

                // Display "Computing: Done" when the hash has been computed
                progressElement.innerHTML = 'Computing: Done';
            };
            reader.readAsArrayBuffer(file);

        } else {
            msg_error.style= "display:block;";
        }
    }
}

/**
 * Computes the SHA-1 hash of a given buffer and returns the hash value in hexadecimal format.
 * @param {ArrayBuffer} buffer - The buffer to hash
 * @returns {Promise<string>} - A promise that resolves to the hexadecimal hash value
 */
function senpai_software_2fa_sha1(buffer) {
    return crypto.subtle.digest("SHA-1", buffer).then(function (hash) {
        return senpai_software_2fa_hex(hash);
    });
}

/**
 * Converts a given buffer into a hexadecimal string.
 * @param {ArrayBuffer} buffer - The buffer to convert
 * @returns {string} - The hexadecimal representation of the buffer
 */
function senpai_software_2fa_hex(buffer) {
    var hexCodes = [];
    var view = new DataView(buffer);
    for (var i = 0; i < view.byteLength; i += 4) {
        var value = view.getUint32(i);
        var stringValue = value.toString(16);
        var padding = "00000000";
        var paddedValue = (padding + stringValue).slice(-padding.length);
        hexCodes.push(paddedValue);
    }
    return hexCodes.join("");
}