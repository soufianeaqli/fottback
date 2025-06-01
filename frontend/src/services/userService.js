/**
 * Service pour gérer les opérations liées au profil utilisateur
 */

/**
 * Met à jour le profil d'un utilisateur
 * @param {Object} userData - Les données du profil à mettre à jour
 * @returns {Promise<Object>} - Les données mises à jour de l'utilisateur
 */
export const updateProfile = async (userData) => {
    try {
        // Afficher les données sans informations sensibles
        const safeData = { ...userData };
        delete safeData.password;
        console.log('Tentative de mise à jour du profil avec:', safeData);
        
        // Convertir les données en JSON et encoder pour l'URL
        const jsonData = encodeURIComponent(JSON.stringify(userData));
        
        // Utiliser le script PHP direct sans CSRF
        const response = await fetch(`http://127.0.0.1:8000/direct-auth.php?action=update-profile&data=${jsonData}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        console.log('Réponse du serveur:', { success: data.success, message: data.message });
        
        if (!data.success) {
            throw new Error(data.message || 'Erreur lors de la mise à jour du profil');
        }
        
        // Mettre à jour les données utilisateur dans localStorage de façon complète
        const storedUser = JSON.parse(localStorage.getItem('user') || '{}');
        const updatedUser = { 
            ...storedUser, 
            username: data.user.username,
            name: data.user.name,
            email: data.user.email,
            phone: data.user.phone,
            role: data.user.role
        };
        localStorage.setItem('user', JSON.stringify(updatedUser));
        console.log('Données utilisateur mises à jour dans localStorage:', {
            username: updatedUser.username,
            name: updatedUser.name,
            email: updatedUser.email,
            phone: updatedUser.phone
        });
        
        return data.user;
    } catch (error) {
        console.error('Erreur lors de la mise à jour du profil:', error);
        throw error;
    }
};

/**
 * Met à jour le mot de passe d'un utilisateur
 * @param {Object} passwordData - Les données de mot de passe (id, current_password, new_password)
 * @returns {Promise<Object>} - Message de confirmation
 */
export const updatePassword = async (passwordData) => {
    try {
        // Ne pas afficher les mots de passe dans les logs
        console.log('Tentative de mise à jour du mot de passe pour l\'utilisateur ID:', passwordData.id);
        
        // Convertir les données en JSON et encoder pour l'URL
        const jsonData = encodeURIComponent(JSON.stringify(passwordData));
        
        // Utiliser le script PHP direct sans CSRF
        const response = await fetch(`http://127.0.0.1:8000/direct-auth.php?action=update-password&data=${jsonData}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        console.log('Réponse du serveur:', { success: data.success, message: data.message });
        
        if (!data.success) {
            throw new Error(data.message || 'Erreur lors de la mise à jour du mot de passe');
        }
        
        return data;
    } catch (error) {
        console.error('Erreur lors de la mise à jour du mot de passe:', error);
        throw error;
    }
}; 