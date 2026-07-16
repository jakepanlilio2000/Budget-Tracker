const DB_NAME = 'ExpenseTrackerDB';
const DB_VERSION = 1;
let db;

const initDB = () => new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);
    request.onupgradeneeded = (e) => {
        db = e.target.result;
        if (!db.objectStoreNames.contains('pending_transactions')) {
            db.createObjectStore('pending_transactions', { keyPath: 'id', autoIncrement: true });
        }
    };
    request.onsuccess = (e) => { db = e.target.result; resolve(db); };
    request.onerror = (e) => reject(e.target.error);
});

const saveOfflineTransaction = async (txnData) => {
    await initDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(['pending_transactions'], 'readwrite');
        const req = tx.objectStore('pending_transactions').add({ ...txnData, timestamp: Date.now() });
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
};

const getPendingTransactions = async () => {
    await initDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(['pending_transactions'], 'readonly');
        const req = tx.objectStore('pending_transactions').getAll();
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
};

const clearPendingTransactions = async () => {
    await initDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(['pending_transactions'], 'readwrite');
        const req = tx.objectStore('pending_transactions').clear();
        req.onsuccess = () => resolve();
        req.onerror = () => reject(req.error);
    });
};
document.addEventListener('DOMContentLoaded', () => {
    const txnForm = document.getElementById('txnForm');
    if (txnForm) {
        txnForm.addEventListener('submit', async (e) => {
            if (!navigator.onLine) {
                e.preventDefault();
                const formData = new FormData(txnForm);
                const data = Object.fromEntries(formData.entries());
                data.splits = [];
                const cats = document.querySelectorAll('.split-cat');
                const amts = document.querySelectorAll('.split-amt');
                const notes = document.querySelectorAll('.split-notes');

                for (let i = 0; i < cats.length; i++) {
                    if (cats[i].value && amts[i].value) {
                        data.splits.push({ category_id: cats[i].value, amount: amts[i].value, notes: notes[i].value });
                    }
                }

                await saveOfflineTransaction(data);
                alert('You are offline. Transaction saved locally and will auto-sync when connection is restored.');
                window.location.href = '/expenses/transactions';
            }
        });
    }
    window.addEventListener('online', syncPendingTransactions);
    if (navigator.onLine) syncPendingTransactions();
});

const syncPendingTransactions = async () => {
    const pending = await getPendingTransactions();
    if (pending.length === 0) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    for (const txn of pending) {
        try {
            const formData = new FormData();
            for (const key in txn) {
                if (key === 'splits') {
                    txn.splits.forEach((split, index) => {
                        formData.append(`split_category[${index}]`, split.category_id);
                        formData.append(`split_amount[${index}]`, split.amount);
                        formData.append(`split_notes[${index}]`, split.notes || '');
                    });
                } else {
                    formData.append(key, txn[key]);
                }
            }
            formData.append('csrf_token', csrfToken);

            const response = await fetch('/expenses/transactions/sync', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (response.ok) {
                await initDB();
                const tx = db.transaction(['pending_transactions'], 'readwrite');
                tx.objectStore('pending_transactions').delete(txn.id);
            }
        } catch (err) {
            console.error('Sync failed for transaction:', txn, err);
        }
    }
};