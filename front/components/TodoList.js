import { urlBackList, urlBackRemove } from "../config.js";

class TodoList extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
    }

    static get styles() {
        return /* css */`
            :host{
            
            }
            table {
                font-family: Arial, Helvetica, sans-serif;
                border-collapse: collapse;
                width: 100%;
            }
            table td, table th{
                border: 1px solid #ddd;
                padding: 8px;
            }
            table td.center div {
                display: flex;
                align-items: center;
                justify-content: center;
                column-gap: 1rem;
            }
            table td.center div span {
                padding: 0.3rem;
                background-color: #CCC;
            }
            table td.center div button {
                border: 0;
                background: none;
                box-shadow: none;
                border-radius: 0px;
                cursor: pointer;
                color: red;
                font-weight: bold;
            }
            table tr:nth-child(even){
                background-color: #f2f2f2;
            }

            table tr:hover {
                background-color: #ddd;
            }

            table th {
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: left;
            background-color: #04AA6D;
            color: white;
            }
        `;
    }

    static get template() {
        return /* html */`
            <table>
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Task</th>
                        <th>Category</th>
                        <th>Actions</th>
                    </tr>
                </tfoot>
            </table>
        `;
    }

    async getModel() {
        const response = await fetch(urlBackList);
        const data = await response.json();
        return (!data.error) ? data.query : {};
    }

    connectedCallback() {
        this.render();
    }

    render() {
        this.shadowRoot.innerHTML = /* html */`
            <style>${TodoList.styles}</style>
            ${TodoList.template}
        `;
        this.update();
    }

    async update(data) {
        const datos = await this.getModel();
        let rows = ``;
        for (const todoId in datos) {
            const element = datos[todoId];
            const todoName = element.todo_name;
            const categories = element.categories;
            const rowSegment = /* html */`
                <tr>
                    <td>${todoName}</td>
                    <td class="center"><div>${this.prepareCategories(categories)}</div></td>
                    <td class="center"><div><button action="remove" data-id="${todoId}">X</button></div></td>
                </tr>
            `;
            rows += rowSegment;
        }
        const tbody = this.shadowRoot.querySelector("tbody");
        tbody.innerHTML = rows;
        this.enableRemoveButtons();
    }

    prepareCategories(categories) {
        let returnCategories = ``;
        for (const categoryId in categories) {
            const categoryName = categories[categoryId];
            const categorySegment = /* html */`
                <span class="category" data-id="${categoryId}">${categoryName}</span>
            `;
            returnCategories += categorySegment;
        }
        return returnCategories;
    }

    enableRemoveButtons(){
        const removeButtons = this.shadowRoot.querySelectorAll("td button[action='remove']");
        Array.from(removeButtons).forEach(button => {
            button.addEventListener("click", ({target}) => {
                const dataId = target.attributes["data-id"].value;
                this.removeTodo(dataId);
            });
        });
    }

    async removeTodo(dataId){
        const response = await fetch(urlBackRemove + dataId, {
            method: "DELETE",
            mode: 'cors',
            headers: {
                'Content-Type': 'application/json'
            },
        });
        this.update();
    }
}
window.customElements.define('todo-list', TodoList);