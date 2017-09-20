import bridge from 'bridge';

export default {
    isGranted : function (role) {
        if (bridge.session.roles.indexOf(role) !== -1) {
            return true;
        }
    },
};