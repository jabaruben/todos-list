import { urlBackAdd } from "../config.js";

class TodoAdd extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
    }

    static get styles() {
        return /* css */`
            :host{
            
            }
            div.addrow {
                margin: 1rem 0;
                display: flex;
                justify-content: center;
                column-gap: 3rem;
            }
            div.addrow>input {
                max-width: 100%;
                width: 100%;
            }
            div.categories {
                display: flex;
                justify-content: center;
                column-gap:1rem;
            }
            div.categories div {
                display: inline-flex;
            }
            button {
                background-color: #4CAF50;
                border: none;
                color: white;
                padding:    5px 32px;
                text-align: center;
                text-decoration: none;
                font-size: 16px;
                cursor: pointer;
            }
        `;
    }

    static get template() {
        return /* html */`
            <div class="addrow">
                <input id="todoname" name="todoname" type="text" placeholder="New ToDo..." />
                <div class="categories">
                    <div>
                        <input type="checkbox" id="checkboxphp" name="checkboxphp" value="1">
                        <label for="checkboxphp">PHP</label>
                    </div>
                    <div>
                        <input type="checkbox" id="checkboxjavascript" name="checkboxjavascript" value="3">
                        <label for="checkboxjavascript">JavaScript</label>
                    </div>
                    <div>
                        <input type="checkbox" id="checkboxcss" name="checkboxcss" value="2">
                        <label for="checkboxcss">CSS</label>
                    </div>
                </div>
                <button action="add">AÑADIR</button>
            </div>
        `;
    }

    connectedCallback() {
        this.render();
        this.addButton = this.shadowRoot.querySelector("button[action='add']");
        this.inputName = this.shadowRoot.querySelector("input#todoname");
        this.checkboxes = this.shadowRoot.querySelectorAll('input[type=checkbox]');
        this.categories = [];
        this.updateCheckboxes();
        Array.from(this.checkboxes).forEach(checkbox => {
            checkbox.addEventListener("change", () => this.updateCheckboxes());
        });
        this.addButton.addEventListener("click", () => this.addTodo());
    }

    updateCheckboxes() {
        const checkboxesSelected = this.shadowRoot.querySelectorAll("input[type=checkbox]:checked");
        this.categories = Array.from(checkboxesSelected, element => {
            return element.value;
        });
    }

    render() {
        this.shadowRoot.innerHTML = /* html */`
            <style>${TodoAdd.styles}</style>
            ${TodoAdd.template}
        `;
    }

    addTodo() {
        const $inputname = this.shadowRoot.querySelector("input#todoname");
        this.updateCheckboxes();
        const categories = this.categories;
        this.save($inputname.value, categories);
    }

    async save(todoname, categories) {
        const params = { todoname, categories };
        const response = await fetch(urlBackAdd, {
            method: 'POST',
            mode: 'cors',
            cache: 'no-cache',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json'
            },
            redirect: 'follow',
            referrerPolicy: 'no-referrer',
            body: JSON.stringify(params)
        });
        const data = await response.json();
        this.clearFields();
        const todoList = document.querySelector("todo-list");
        todoList.update();
    }

    clearFields(){
        this.inputName.value = "";
        Array.from(this.checkboxes).forEach(checkbox => {
            checkbox.checked = false;
        });
    }
}
window.customElements.define('todo-add', TodoAdd);