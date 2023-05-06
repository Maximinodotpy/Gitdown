function renderJson({root = '', data, depth = 0, top_level_root = document.body} = {}) {

    if (depth == 0 && root == '') {
        const pre = document.createElement('pre')
        const ul = document.createElement('ul')

        pre.appendChild(ul)
        root = ul
        top_level_root.appendChild(pre)
    }
    else {
        root.innerHTML = ''
    }

    for (d in data) {
        if (typeof data[d] == 'object' && data[d] != null) {
            const nestedData = data[d]

            const detailsElement = document.createElement('details')
            const summaryEl = document.createElement('summary')
            summaryEl.classList.add('titleStyle')

            detailsElement.appendChild(summaryEl)


            let appendedString = Array.isArray(data[d]) ? `Array, ${data[d].length}` : 'Object'

            summaryEl.innerHTML = `${d} <span class="titleStyleDescription">(${appendedString})<span></summary>`

            const newRoot = document.createElement('ul')

            detailsElement.appendChild(newRoot)

            root.appendChild(detailsElement)

            summaryEl.addEventListener('click', () => {
                if ( !detailsElement.hasAttribute('open') ) {
                    renderJson({
                            root: newRoot,
                            data: nestedData,
                            depth: depth + 1
                        })
                    clicked = true
                }
                else {
                    newRoot.innerHTML = ''
                }
            })
        }
        else {
            let currentType = typeof data[d]
            let el = document.createElement('li')
            let display = null

            switch (currentType) {
                case 'object':
                    display = 'null'
                    break;
                default:
                    display = data[d]
                    break;
            }

            let titleSpan = document.createElement('span')
            let contentSpan = document.createElement('span')
            let detailsContentSpan = document.createElement('span')

            titleSpan.innerText = `${d}: `
            titleSpan.classList.add('titleStyle')

            contentSpan.innerText = display
            contentSpan.classList.add(currentType)

            detailsContentSpan.innerText = `   Type: ${currentType}; Length: ${display?.length}; Boolean: ${Boolean(display)}`
            detailsContentSpan.classList.add('moreDetails')

            el.appendChild(titleSpan)
            el.appendChild(contentSpan)
            el.appendChild(detailsContentSpan)

            root.appendChild(el)
        }
    }
}


console.log('JSON.js loaded ...');