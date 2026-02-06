import { Controller } from "@hotwired/stimulus"

/**
 * Inline Edit Controller (minimal)
 *
 * Provides enhancements for the Turbo-native inline edit pattern:
 * - Escape key to cancel (clicks the cancel link)
 * - Click outside to submit (auto-save)
 * - Auto-focus and place cursor at end when form loads
 * - Enter to submit for text inputs
 * - Auto-grow input to fit content
 */
export default class extends Controller {
    static targets = ["cancel", "sizer"]

    connect() {
        // When frame content changes, check if it's a form and set up behavior
        this.element.addEventListener("turbo:frame-load", this.onFrameLoad.bind(this))
        this.boundClickOutside = this.clickOutside.bind(this)
    }

    disconnect() {
        document.removeEventListener("click", this.boundClickOutside)
    }

    onFrameLoad() {
        const form = this.element.querySelector("form")
        const input = this.element.querySelector("input, textarea, select")

        if (form && input) {
            // We're in edit mode - set up behaviors
            this.setupEditMode(form, input)
        } else {
            // We're in display mode - clean up
            document.removeEventListener("click", this.boundClickOutside)
        }
    }

    setupEditMode(form, input) {
        // Auto-focus and place cursor at end
        input.focus()
        if (input.setSelectionRange && input.value) {
            const len = input.value.length
            input.setSelectionRange(len, len)
        }

        // Auto-grow input to fit content
        if (input.tagName === "INPUT" && this.hasSizerTarget) {
            this.setupAutoGrow(input)
        }

        // Enter to submit for text inputs
        if (input.tagName === "INPUT" && !["checkbox", "radio", "file"].includes(input.type)) {
            input.addEventListener("keydown", (e) => {
                if (e.key === "Enter") {
                    e.preventDefault()
                    form.requestSubmit()
                }
            })
        }

        // Cmd/Ctrl+Enter for textarea
        if (input.tagName === "TEXTAREA") {
            input.addEventListener("keydown", (e) => {
                if (e.key === "Enter" && (e.metaKey || e.ctrlKey)) {
                    e.preventDefault()
                    form.requestSubmit()
                }
            })
        }

        // Click outside to submit - add listener on next tick to avoid immediate trigger
        setTimeout(() => {
            document.addEventListener("click", this.boundClickOutside)
        }, 0)
    }

    setupAutoGrow(input) {
        const sizer = this.sizerTarget
        const padding = 24 // Extra space for comfortable typing

        const resize = () => {
            sizer.textContent = input.value || input.placeholder || ""
            input.style.width = (sizer.offsetWidth + padding) + "px"
        }

        // Initial size
        resize()

        // Resize on input
        input.addEventListener("input", resize)
    }

    /**
     * Click outside the frame submits the form
     */
    clickOutside(event) {
        // Ignore if click is inside this element
        if (this.element.contains(event.target)) {
            return
        }

        const form = this.element.querySelector("form")
        if (form) {
            document.removeEventListener("click", this.boundClickOutside)
            form.requestSubmit()
        }
    }

    /**
     * Escape key cancels editing
     */
    keydown(event) {
        if (event.key === "Escape" && this.hasCancelTarget) {
            event.preventDefault()
            document.removeEventListener("click", this.boundClickOutside)
            this.cancelTarget.click()
        }
    }
}
