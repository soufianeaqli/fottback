/**
 * Service pour gérer les messages de contact
 */

/**
 * Envoie un nouveau message de contact
 * @param {Object} contactData - Les données du message de contact
 * @returns {Promise<Object>} - La réponse du serveur
 */
export const sendContactMessage = async (contactData) => {
    try {
        console.log('Envoi du message de contact:', contactData);
        
        // Convertir les données en JSON et encoder pour l'URL
        const jsonData = encodeURIComponent(JSON.stringify(contactData));
        
        // Utiliser le script PHP direct sans CSRF
        const response = await fetch(`http://127.0.0.1:8000/direct-contact.php?action=add&data=${jsonData}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        console.log('Réponse du serveur:', data);
        
        if (!data.success) {
            throw new Error(data.message || 'Erreur lors de l\'envoi du message');
        }
        
        return data;
    } catch (error) {
        console.error('Erreur lors de l\'envoi du message de contact:', error);
        throw error;
    }
};

/**
 * Récupère tous les messages de contact
 * @returns {Promise<Array>} - La liste des messages de contact
 */
export const getContactMessages = async () => {
    try {
        console.log('Récupération des messages de contact');
        
        // Utiliser le script PHP direct sans CSRF
        const response = await fetch('http://127.0.0.1:8000/direct-contact.php?action=get', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        console.log('Réponse du serveur:', data);
        
        if (!data.success) {
            throw new Error(data.message || 'Erreur lors de la récupération des messages');
        }
        
        return data.data;
    } catch (error) {
        console.error('Erreur lors de la récupération des messages de contact:', error);
        throw error;
    }
};

/**
 * Marque un message de contact comme lu
 * @param {number} id - L'ID du message de contact
 * @returns {Promise<Object>} - La réponse du serveur
 */
export const markContactMessageAsRead = async (id) => {
    try {
        console.log('Marquage du message comme lu:', id);
        
        // Utiliser le script PHP direct sans CSRF
        const response = await fetch(`http://127.0.0.1:8000/direct-contact.php?action=mark-read&id=${id}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        console.log('Réponse du serveur:', data);
        
        if (!data.success) {
            throw new Error(data.message || 'Erreur lors du marquage du message');
        }
        
        return data;
    } catch (error) {
        console.error('Erreur lors du marquage du message de contact:', error);
        throw error;
    }
};

/**
 * Supprime un message de contact
 * @param {number} id - L'ID du message de contact
 * @returns {Promise<Object>} - La réponse du serveur
 */
export const deleteContactMessage = async (id) => {
    try {
        console.log('Suppression du message:', id);
        
        // Utiliser le script PHP direct sans CSRF
        const response = await fetch(`http://127.0.0.1:8000/direct-contact.php?action=delete&id=${id}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        console.log('Réponse du serveur:', data);
        
        if (!data.success) {
            throw new Error(data.message || 'Erreur lors de la suppression du message');
        }
        
        return data;
    } catch (error) {
        console.error('Erreur lors de la suppression du message de contact:', error);
        throw error;
    }
}; 