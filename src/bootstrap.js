import hooks from "./hooks"

export const Bootstrap = ({ Vue, router }) => {

    const event = new CustomEvent("wpum-api-ready", {
		detail: {
			Vue,
			Router: router,
			Hooks: hooks
		}
	});
    window.dispatchEvent(event);
}
