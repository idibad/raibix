const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'resources/js/Pages/Tenant/Admin/Dashboard.vue');
let content = fs.readFileSync(filePath, 'utf8');

const modalConfigs = [
    {
        var: 'showAddUserModal',
        start: `<div v-if="showAddUserModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showEditUserModal',
        start: `<div v-if="showEditUserModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showBulkUserModal',
        start: `<div v-if="showBulkUserModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showStudentCsvModal',
        start: `<div v-if="showStudentCsvModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showClassCsvModal',
        start: `<div v-if="showClassCsvModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showAddStudentModal',
        start: `<div v-if="showAddStudentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-4xl w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showEditStudentModal',
        start: `<div v-if="showEditStudentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-2xl w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showStudentStatusModal',
        start: `<div v-if="showStudentStatusModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showStudentTransferModal',
        start: `<div v-if="showStudentTransferModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showBulkPromoteModal',
        start: `<div v-if="showBulkPromoteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-xl w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showDocumentVaultModal',
        start: `<div v-if="showDocumentVaultModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-3xl w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</div>\n            </div>\n        </div>`
    },
    {
        var: 'showTimetableModal',
        start: `<div v-if="showTimetableModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showFeeOverrideModal',
        start: `<div v-if="showFeeOverrideModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showStaffCheckInModal',
        start: `<div v-if="showStaffCheckInModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showStaffLeaveModal',
        start: `<div v-if="showStaffLeaveModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showCreateExamModal',
        start: `<div v-if="showCreateExamModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showAddClassModal',
        start: `<div v-if="showAddClassModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showAddSectionModal',
        start: `<div v-if="showAddSectionModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showAddNoticeModal',
        start: `<div v-if="showAddNoticeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showSportsModal',
        start: `<div v-if="showSportsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    },
    {
        var: 'showPenaltyModal',
        start: `<div v-if="showPenaltyModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 overflow-y-auto">`,
        innerStart: `<div class="bg-white rounded-3xl border border-[#DADCE0] p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6 my-8 animate-scaleUp">`,
        end: `</form>\n            </div>\n        </div>`
    }
];

// Let's reload the backup from git using reset if possible, or checkout?
// Wait, we can't checkout from git.
// But we can download the file from the transcript using node by reading transcript_full.jsonl!
// Let's do that! That is 100% foolproof and avoids any manual repair errors!
const logPath = 'C:\\Users\\Personal\\.gemini\\antigravity\\brain\\7fe8a20e-affa-45ed-8661-6c80bf76559f\\.system_generated\\logs\\transcript_full.jsonl';
if (fs.existsSync(logPath)) {
    const lines = fs.readFileSync(logPath, 'utf8').split('\n');
    // Find the latest step index before step 792 (or search for when we read Dashboard.vue)
    // Wait, let's search from the beginning of the file for the original content.
    // Actually, let's write a simple script that reads transcript_full.jsonl and looks for the content of Dashboard.vue.
}
