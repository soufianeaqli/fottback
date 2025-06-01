const API_URL = 'http://127.0.0.1:8000/api';

const getCSRFToken = async () => {
    try {
        await fetch('http://127.0.0.1:8000/sanctum/csrf-cookie', {
            credentials: 'include'
        });
    } catch (error) {
        console.error('Error fetching CSRF token:', error);
    }
};

const handleResponse = async (response) => {
    const data = await response.json();
    if (!response.ok) {
        throw new Error(data.message || 'Une erreur est survenue');
    }
    return data;
};

export const getAllTournaments = async () => {
    try {
        const response = await fetch(`${API_URL}/tournaments`, {
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });
        return await handleResponse(response);
    } catch (error) {
        console.error('Error fetching tournaments:', error);
        throw error;
    }
};

export const getTournamentById = async (id) => {
    try {
        const response = await fetch(`${API_URL}/tournaments/${id}`, {
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });
        return await handleResponse(response);
    } catch (error) {
        console.error('Error fetching tournament:', error);
        throw error;
    }
};

export const createTournament = async (tournamentData) => {
    try {
        // Convertir les données en JSON et encoder pour l'URL
        const jsonData = encodeURIComponent(JSON.stringify(tournamentData));
        
        // Utiliser la route sans CSRF avec méthode GET
        const response = await fetch(`http://127.0.0.1:8000/api/no-csrf/add-tournament?data=${jsonData}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'Erreur lors de la création du tournoi');
        }
        return data;
    } catch (error) {
        console.error('Error creating tournament:', error);
        throw error;
    }
};

export const updateTournament = async (id, tournamentData) => {
    try {
        // Convertir les données en JSON et encoder pour l'URL
        const jsonData = encodeURIComponent(JSON.stringify(tournamentData));
        
        // Utiliser la route sans CSRF avec méthode GET
        const response = await fetch(`http://127.0.0.1:8000/api/no-csrf/update-tournament/${id}?data=${jsonData}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'Erreur lors de la modification du tournoi');
        }
        return data;
    } catch (error) {
        console.error('Error updating tournament:', error);
        throw error;
    }
};

export const deleteTournament = async (id) => {
    try {
        const response = await fetch(`http://127.0.0.1:8000/api/no-csrf/delete-tournament/${id}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'Erreur lors de la suppression du tournoi');
        }
        return true;
    } catch (error) {
        console.error('Error deleting tournament:', error);
        throw error;
    }
};

export const registerTeam = async (tournamentId, teamData) => {
    try {
        // S'assurer que l'utilisateur connecté est associé à cette équipe
        // en ajoutant l'ID utilisateur si disponible dans localStorage
        const userStr = localStorage.getItem('user');
        if (userStr) {
            try {
                const user = JSON.parse(userStr);
                // Ajouter l'ID utilisateur aux données de l'équipe si disponible
                if (user && user.id) {
                    teamData.user_id = user.id;
                }
                console.log('User info added to team data:', user, teamData);
            } catch (e) {
                console.error('Error parsing user data from localStorage', e);
            }
        }
        
        // Convertir les données en JSON et encoder pour l'URL
        const jsonData = encodeURIComponent(JSON.stringify(teamData));
        
        console.log('Sending registration data:', teamData, 'for tournament:', tournamentId);
        
        // Utiliser la route sans CSRF avec méthode GET
        const response = await fetch(`http://127.0.0.1:8000/api/no-csrf/register-team-tournament/${tournamentId}?data=${jsonData}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            console.error('Error response:', data);
            throw new Error(data.message || 'Erreur lors de l\'inscription de l\'équipe');
        }
        
        console.log('Registration successful:', data);
        return data;
    } catch (error) {
        console.error('Error registering team:', error);
        throw error;
    }
};

export const unregisterTeam = async (tournamentId, teamId = null) => {
    try {
        let url = `http://127.0.0.1:8000/api/no-csrf/unregister-team-tournament/${tournamentId}?`;
        
        // Si nous avons un ID d'équipe, l'utiliser en priorité
        if (teamId) {
            url += `team_id=${teamId}`;
            console.log('Utilisera l\'ID d\'équipe pour la désinscription:', teamId);
        } else {
            // Sinon, essayer de trouver l'utilisateur connecté
            console.log('Pas d\'ID d\'équipe fourni, recherche des informations utilisateur...');
            const userStr = localStorage.getItem('user');
            console.log('Données utilisateur en localStorage:', userStr);
            
            // Vérifier si on a les données utilisateur
            if (!userStr) {
                console.error('Aucune donnée utilisateur en localStorage');
                throw new Error('Aucune information d\'utilisateur disponible pour la désinscription');
            }
            
            try {
                const user = JSON.parse(userStr);
                console.log('Données utilisateur parsing JSON:', user);
                
                if (user && user.id) {
                    url += `user_id=${user.id}`;
                    console.log('Utilisera l\'ID utilisateur pour la désinscription:', user.id);
                } else if (user && user.email) {
                    url += `email=${encodeURIComponent(user.email)}`;
                    console.log('Utilisera l\'email pour la désinscription:', user.email);
                } else if (user && user.username) {
                    // Ajouter le support pour l'identifiant par nom d'utilisateur
                    url += `captain=${encodeURIComponent(user.username)}`;
                    console.log('Utilisera le nom d\'utilisateur pour la désinscription:', user.username);
                } else {
                    console.error('Données utilisateur insuffisantes:', user);
                    throw new Error('Impossible de déterminer l\'utilisateur à désinscrire');
                }
            } catch (e) {
                console.error('Error parsing user data from localStorage', e);
                throw new Error('Erreur lors de la récupération des informations utilisateur');
            }
        }
        
        console.log('Sending unregistration request to:', url);
        
        // Utiliser la route sans CSRF avec méthode GET
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        console.log('Réponse brute de l\'API:', data);
        
        if (!response.ok) {
            console.error('Error response from unregister:', data);
            throw new Error(data.message || 'Erreur lors de la désinscription de l\'équipe');
        }
        console.log('Unregistration successful:', data);
        return data;
    } catch (error) {
        console.error('Error unregistering team:', error);
        throw error;
    }
}; 