let rememberedUser = null;

export function rememberUser(user) {
    rememberedUser = user;
}

export function getRememberedUser() {
    return rememberedUser;
}
