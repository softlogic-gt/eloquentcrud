<template>
    <div>
        <table class="table table-sm">
            <tbody>
                <tr v-for="(filter, key) in filters">
                    <td>
                        <select class="form-control" aria-label="Filter" v-model="filter.column">
                            <option :value="header" v-for="header in props.headers">
                                {{ header.header }}
                            </option>
                        </select>
                    </td>
                    <td>
                        <select class="form-control" aria-label="Filter" v-model="filter.operator">
                            <option value="=">=</option>
                            <option value="LIKE">Contiene</option>
                            <option value=">">&gt;</option>
                            <option value="<">&lt;</option>
                        </select>
                    </td>
                    <td>
                        <template v-if="filter.column">
                            <select class="form-control" v-if="filter.column.type == 'boolean'" v-model="filter.value">
                                <option value="1">si</option>
                                <option value="0">no</option>
                            </select>
                            <input type="text" class="form-control" v-model="filter.value" v-else />
                        </template>
                    </td>
                    <td>
                        <template v-if="key == filters.length - 1">
                            <button class="btn btn-outline-secondary" @click="newFilter">
                                <i class="fa fa-plus text-success"></i>
                            </button>
                            <button class="btn btn-outline-secondary" @click="refresh()">
                                <i class="fa fa-search"></i>
                            </button>
                        </template>
                        <template v-else>
                            <button class="btn btn-outline-secondary" @click="destroyFilter(filter.id)">
                                <i class="fa fa-times text-danger"></i>
                            </button>
                        </template>
                    </td>
                </tr>
            </tbody>
        </table>
        <table class="table table-striped table-sm table-bordered">
            <thead>
                <tr>
                    <th v-for="header in props.headers" @click="sortBy(header.column)">
                        {{ header.header }}
                        <span v-if="sort.column == header.column">
                            <i :class="sort.direction === 'asc' ? 'fa-solid fa-sort-up' : 'fa-solid fa-sort-down'"></i>
                        </span>
                        <i class="fa-solid fa-sort" style="opacity: 20%" v-else></i>
                    </th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="columns in result.data">
                    <template v-for="(column, name) in columns">
                        <td v-if="name != '__id__'" v-html="formatted(column, name)"></td>
                    </template>
                    <td class="text-right">
                        <div class="btn-group" role="group" aria-label="Actions">
                            <a
                                :href="prepareUrl(extrabutton.url, columns)"
                                :target="extrabutton.target"
                                v-for="extrabutton in props.extrabuttons"
                                type="button"
                                class="btn btn-default"
                            >
                                <i :class="extrabutton.icon + ' fa-fw ' + extrabutton.class"></i>
                            </a>

                            <button
                                v-if="props.permissions.update"
                                type="button"
                                class="btn btn-default"
                                @click="edit(columns['__id__'])"
                            >
                                <i class="fa fa-pencil fa-fw text-primary"></i>
                            </button>
                            <button
                                v-if="props.permissions.destroy"
                                type="button"
                                class="btn btn-default"
                                @click="destroy(columns['__id__'])"
                            >
                                <i class="fa fa-trash fa-fw text-danger"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="row">
            <div class="col-sm-6" style="margin-top: 30px">
                <p class="small text-muted">
                    Showing
                    <span class="fw-semibold">{{ result.from }}</span>
                    to
                    <span class="fw-semibold">{{ result.to }}</span>
                    of
                    <span class="fw-semibold">{{ result.total }}</span>
                    results
                </p>
            </div>

            <div class="col-sm-6 text-right">
                <ul class="pagination pagination-sm">
                    <li
                        :class="{
                            'page-item': true,
                            disabled: link.url == null,
                            active: link.active,
                        }"
                        aria-disabled="true"
                        aria-label="«"
                        v-for="link in result.links"
                    >
                        <span
                            class="page-link"
                            aria-hidden="true"
                            @click="refresh(link.url)"
                            v-html="link.label"
                        ></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
<script setup>
import axios from "axios";
import { onMounted, ref } from "vue";

const result = ref([]);
const filtered = ref(false);
const links = ref("");
const filters = ref([
    {
        id: 0,
        column: null,
        operator: "=",
        value: null,
    },
]);
const sort = ref({
    column: null,
    direction: "asc",
});
const props = defineProps({
    headers: Array,
    extrabuttons: Array,
    casts: Object,
    permissions: Object,
    baseurl: String,
});

onMounted(() => {
    refresh();
});

function formatted(column, name) {
    if (props.casts.hasOwnProperty(name)) {
        if (props.casts[name] == "boolean") {
            return column == 1
                ? "<i class='fa fa-check fa-fw text-success'/>"
                : "<i class='fa fa-times fa-fw text-danger'/>";
        }
    }
    return column;
}

function sortBy(column) {
    console.log(column);
    if (sort.value.column == column) {
        sort.value.direction = sort.value.direction == "asc" ? "desc" : "asc";
    } else {
        sort.value.column = column;
    }
    refresh();
}

function refresh($url = props.baseurl + "/data") {
    axios
        .post($url, {
            filters: filters.value,
            sort: sort.value,
        })
        .then((res) => {
            result.value = res.data.result;
            filtered.value = res.data.filtered;
            links.value = res.data.links;
        })
        .catch((err) => {
            console.log(err);
        });
}

function edit(id) {
    window.location = props.baseurl + "/" + id + "/edit";
}

function destroy(id) {
    if (!confirm("¿Está seguro que desea eliminar este registro?")) {
        return;
    }

    axios
        .delete(props.baseurl + "/" + id)
        .then((res) => {
            const deletedId = res.data.id;
            rows.value = rows.value.filter((row) => {
                return row["__id__"] != deletedId;
            });
        })
        .catch((err) => {
            console.log(err);
        });
}

function newFilter() {
    filters.value.push({
        id: Math.random(),
        column: null,
        operator: "=",
        value: null,
    });
}

function destroyFilter(id) {
    filters.value = filters.value.filter((f) => f.id != id);
}

function prepareUrl(url, data) {
    url = url.replace("{id}", data["__id__"]);
    return url.replace(/{(\w+)}/g, (match, key) => data[key] || match);
}
</script>
