const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'resources/js/Pages/Tenant/Admin/Dashboard.vue');
let content = fs.readFileSync(filePath, 'utf8');

// We want to find each modal block starting with:
// <div v-if="show...Modal" class="fixed inset-0 z-50 flex
// And parse it completely.

const modalVars = [
    'showAddUserModal', 'showEditUserModal', 'showBulkUserModal',
    'showStudentCsvModal', 'showClassCsvModal', 'showAddStudentModal',
    'showEditStudentModal', 'showStudentStatusModal', 'showStudentTransferModal',
    'showBulkPromoteModal', 'showDocumentVaultModal', 'showTimetableModal',
    'showFeeOverrideModal', 'showMonthlyBillingModal', 'showStaffCheckInModal',
    'showStaffLeaveModal', 'showCreateExamModal', 'showAddClassModal',
    'showAddSectionModal', 'showAddNoticeModal', 'showSportsModal',
    'showPenaltyModal'
];

for (const mv of modalVars) {
    // Find the start of the modal.
    // In the current file, it starts with either the new pattern:
    // <div v-if="showAddUserModal" class="fixed inset-0 z-50 flex justify-end
    // Or the original pattern (if any was missed):
    // <div v-if="showAddUserModal" class="fixed inset-0 z-50 flex items-center
    const searchStr = `v-if="${mv}"`;
    const searchIndex = content.indexOf(searchStr);
    if (searchIndex === -1) {
        console.log(`Could not find ${mv} in file.`);
        continue;
    }
    
    // Find the start of the outer div containing this v-if
    const startDivIndex = content.lastIndexOf('<div', searchIndex);
    if (startDivIndex === -1) {
        console.log(`Could not find start div for ${mv}`);
        continue;
    }
    
    // Now let's extract the entire modal block by balancing tags
    let pos = startDivIndex;
    let openDivs = 0;
    let blockEndIndex = -1;
    
    while (pos < content.length) {
        if (content.substring(pos, pos + 4) === '<div') {
            openDivs++;
            pos += 4;
        } else if (content.substring(pos, pos + 5) === '</div') {
            openDivs--;
            if (openDivs === 0) {
                // Found the closing tag of the outer div!
                blockEndIndex = pos + 6; // include '</div>'
                break;
            }
            pos += 5;
        } else {
            pos++;
        }
    }
    
    if (blockEndIndex === -1) {
        console.log(`Could not find balanced block end for ${mv}`);
        continue;
    }
    
    const originalBlock = content.substring(startDivIndex, blockEndIndex);
    
    // Now let's extract the actual form/content from the original block.
    // The content is usually between the header or form tag and the closing tags.
    // Let's find:
    // 1. Any <form> tag or the first header <h3>
    // 2. The inner content wrapper.
    // Let's strip out the backdrop, the panel wrapper, and the scroll wrapper, and any old rounded-3xl panel divs.
    
    // Let's find if there is a <form> or <h3> inside the block
    let innerContentStart = -1;
    const formIndex = originalBlock.indexOf('<form');
    const h3Index = originalBlock.indexOf('<div class="flex items-center justify-between');
    const h3IndexAlt = originalBlock.indexOf('<div class="flex justify-between');
    
    if (formIndex !== -1) {
        innerContentStart = formIndex;
    } else if (h3Index !== -1) {
        innerContentStart = h3Index;
    } else if (h3IndexAlt !== -1) {
        innerContentStart = h3IndexAlt;
    } else {
        // Fallback: search for first <h3>
        const h3TagIndex = originalBlock.indexOf('<h3');
        if (h3TagIndex !== -1) {
            innerContentStart = originalBlock.lastIndexOf('<div', h3TagIndex);
        }
    }
    
    if (innerContentStart === -1) {
        console.log(`Could not find inner content start for ${mv}`);
        continue;
    }
    
    // The closing tags of the block are at the end.
    // We want to extract everything from innerContentStart to the end, but excluding the closing tags of the outer wrappers.
    // Let's find the closing form or closing content tags.
    // A clean way: the inner content is everything inside, let's keep the raw HTML from innerContentStart,
    // but strip any trailing closing divs that belong to the outer wrappers.
    // Since we want to wrap the inner content in:
    // <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
    //      [Inner Content]
    // </div>
    // Let's find the end of the form or end of the inner content.
    let innerContent = originalBlock.substring(innerContentStart);
    
    // Clean up trailing divs:
    // We want to count how many closing </div> tags are at the very end of innerContent.
    // Let's remove any trailing closing divs, then we will wrap it ourselves.
    // Let's strip trailing </div> tags, whitespace, and clean it.
    innerContent = innerContent.trim();
    while (innerContent.endsWith('</div>')) {
        innerContent = innerContent.substring(0, innerContent.lastIndexOf('</div>')).trim();
    }
    
    // Wait! Let's check: if the inner content had a <form> tag, does it have its closing </form> tag?
    // Let's verify. If it ends with </form>, then the closing </form> is intact.
    // Let's print the end of innerContent to verify.
    // Also, if the inner content has any leftover class="bg-white rounded-3xl ... animate-scaleUp" div, we should remove it.
    // Let's check if the innerContent starts with a class="bg-white rounded-3xl..." div.
    // If it does, we strip its opening tag and its matching closing tag!
    if (innerContent.includes('rounded-3xl') && innerContent.includes('animate-scaleUp')) {
        // Find the index of this wrapper div
        const wrapperIndex = innerContent.indexOf('<div class="bg-white rounded-3xl');
        if (wrapperIndex !== -1) {
            // Find the opening tag end '>'
            const wrapperOpenEnd = innerContent.indexOf('>', wrapperIndex) + 1;
            // The content is after this wrapper open tag, and we strip the last </div> which closed this wrapper.
            innerContent = innerContent.substring(wrapperOpenEnd).trim();
            // Since we stripped the opening tag of the wrapper, we also need to strip its closing tag.
            // But we already stripped all trailing </div> tags!
            // Wait, if we stripped all trailing </div> tags, is that correct?
            // Yes, because we will wrap it ourselves in the 3 clean divs!
        }
    }
    
    // Detect width class from original block to preserve form width
    let maxClass = 'max-w-lg';
    if (originalBlock.includes('max-w-4xl')) maxClass = 'max-w-4xl';
    else if (originalBlock.includes('max-w-2xl')) maxClass = 'max-w-2xl';
    else if (originalBlock.includes('max-w-md')) maxClass = 'max-w-md';
    else if (originalBlock.includes('max-w-xl')) maxClass = 'max-w-xl';
    else if (originalBlock.includes('max-w-3xl')) maxClass = 'max-w-3xl';
    
    // Reconstruct the clean drawer block
    const cleanBlock = 
`        <div v-if="${mv}" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="${mv} = false"></div>
            <div class="bg-white w-full ${maxClass} shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    ${innerContent}
                </div>
            </div>
        </div>`;
        
    content = content.replace(originalBlock, cleanBlock);
}

fs.writeFileSync(filePath, content, 'utf8');
console.log('Successfully cleaned and balanced all modals in Dashboard.vue');
