const fs = require('fs');

function removeTags(file) {
    if (!fs.existsSync(file)) return;
    let text = fs.readFileSync(file, 'utf8');
    
    // Remove "P... Engine" tags
    text = text.replace(/<span class="[^"]*">\s*P[0-9]+ #[0-9]+ Engine\s*<\/span>/g, '');
    
    fs.writeFileSync(file, text);
    console.log('Processed', file);
}

removeTags('c:/Users/Personal/Desktop/LMS/resources/js/Pages/Tenant/Admin/Dashboard.vue');
removeTags('c:/Users/Personal/Desktop/LMS/resources/js/Pages/Tenant/Student/Dashboard.vue');
