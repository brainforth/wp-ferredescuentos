jQuery(document).ready(function($) {
    $('#start-import').on('click', function() {
        const fileInput = document.getElementById('zip-file');
        const file = fileInput.files[0];
        
        if (!file) {
            alert('Selecciona un archivo ZIP');
            return;
        }

        $('#progress-container, #log-container').show();
        $('#progress-fill').css('width', '0%');
        $('#progress-text').text('0%');
        $('#current-sku').text('');
        $('#log-output').text('Preparando importación...\n');
        
        const formData = new FormData();
        formData.append('zip_file', file);
        formData.append('action', 'process_zip');
        formData.append('nonce', sku_importer_vars.nonce);

        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percent = (e.loaded / e.total) * 100;
                $('#progress-fill').css('width', percent + '%');
                $('#progress-text').text(percent.toFixed(1) + '%');
            }
        });
        
        let receivedData = '';
        xhr.onreadystatechange = function() {
            if (xhr.readyState > 2) {
                const newData = xhr.responseText.substring(receivedData.length);
                receivedData = xhr.responseText;
                
                const lines = newData.split('\n');
                for (const line of lines) {
                    if (line.trim() === '') continue;
                    
                    try {
                        const response = JSON.parse(line);
                        if (response.progress !== undefined) {
                            $('#progress-fill').css('width', response.progress + '%');
                            $('#progress-text').text(response.progress + '%');
                        }
                        if (response.current) {
                            $('#current-sku').text('SKU actual: ' + response.current);
                        }
                        if (response.log) {
                            $('#log-output').append(response.log + '\n');
                        }
                    } catch (e) {
                        $('#log-output').append(line + '\n');
                    }
                }
                
                const logOutput = document.getElementById('log-output');
                logOutput.scrollTop = logOutput.scrollHeight;
            }
            
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                } else {
                    $('#log-output').append('\nError en el servidor: ' + xhr.statusText);
                }
            }
        };
        
        xhr.onerror = function() {
            $('#log-output').append('\nError de conexión');
        };
        
        xhr.open('POST', sku_importer_vars.ajax_url, true);
        xhr.send(formData);
    });
});