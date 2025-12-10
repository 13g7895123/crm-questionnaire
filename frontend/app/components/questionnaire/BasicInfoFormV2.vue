<template>
  <div class="space-y-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">公司基本資料</h2>

    <!-- 公司資訊 -->
    <div class="border-b pb-6">
      <h3 class="text-xl font-semibold text-gray-700 mb-4">公司資訊</h3>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            公司名稱 <span class="text-red-500">*</span>
          </label>
          <input
            v-model="formData.companyName"
            type="text"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="請輸入公司名稱"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            公司地址
          </label>
          <textarea
            v-model="formData.companyAddress"
            rows="3"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="請輸入完整地址"
          />
        </div>
      </div>
    </div>

    <!-- 員工統計 -->
    <div class="border-b pb-6">
      <h3 class="text-xl font-semibold text-gray-700 mb-4">員工統計 <span class="text-red-500">*</span></h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            總人數 <span class="text-red-500">*</span>
          </label>
          <input
            v-model.number="formData.employees.total"
            type="number"
            min="0"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="0"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            男性
          </label>
          <input
            v-model.number="formData.employees.male"
            type="number"
            min="0"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="0"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            女性
          </label>
          <input
            v-model.number="formData.employees.female"
            type="number"
            min="0"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="0"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            外籍員工
          </label>
          <input
            v-model.number="formData.employees.foreign"
            type="number"
            min="0"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="0"
          />
        </div>
      </div>
    </div>

    <!-- 設施資訊 -->
    <div class="border-b pb-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-semibold text-gray-700">
          設施資訊 <span class="text-red-500">*</span>
        </h3>
        <button
          type="button"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
          @click="addFacility"
        >
          + 新增設施
        </button>
      </div>

      <div class="space-y-4">
        <div
          v-for="(facility, index) in formData.facilities"
          :key="index"
          class="p-4 bg-gray-50 rounded-lg border border-gray-200"
        >
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-medium text-gray-700">設施 {{ index + 1 }}</h4>
            <button
              v-if="formData.facilities.length > 1"
              type="button"
              class="text-red-600 hover:text-red-800 text-sm"
              @click="removeFacility(index)"
            >
              刪除
            </button>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                地點名稱 <span class="text-red-500">*</span>
              </label>
              <input
                v-model="facility.location"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                placeholder="例：台北工廠"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                地址 <span class="text-red-500">*</span>
              </label>
              <input
                v-model="facility.address"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                placeholder="完整地址"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                面積 (平方公尺)
              </label>
              <input
                v-model="facility.area"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                placeholder="例：5000"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                類型
              </label>
              <input
                v-model="facility.type"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                placeholder="例：製造、研發、倉儲"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 認證資訊 -->
    <div class="border-b pb-6">
      <h3 class="text-xl font-semibold text-gray-700 mb-4">認證資訊</h3>
      <div class="space-y-3">
        <div class="flex items-center space-x-2">
          <input
            v-model="newCertification"
            type="text"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="例：ISO 9001, ISO 14001"
            @keypress.enter="addCertification"
          />
          <button
            type="button"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            @click="addCertification"
          >
            新增
          </button>
        </div>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="(cert, index) in formData.certifications"
            :key="index"
            class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm"
          >
            {{ cert }}
            <button
              type="button"
              class="ml-2 text-blue-600 hover:text-blue-800"
              @click="removeCertification(index)"
            >
              ×
            </button>
          </span>
        </div>
      </div>
    </div>

    <!-- RBA 會員 -->
    <div class="border-b pb-6">
      <h3 class="text-xl font-semibold text-gray-700 mb-4">RBA 線上會員</h3>
      <div class="flex items-center space-x-4">
        <label class="flex items-center space-x-2 cursor-pointer">
          <input
            v-model="formData.rbaOnlineMember"
            type="radio"
            :value="true"
            class="w-4 h-4 text-blue-600"
          />
          <span class="text-sm font-medium text-gray-700">是</span>
        </label>
        <label class="flex items-center space-x-2 cursor-pointer">
          <input
            v-model="formData.rbaOnlineMember"
            type="radio"
            :value="false"
            class="w-4 h-4 text-blue-600"
          />
          <span class="text-sm font-medium text-gray-700">否</span>
        </label>
      </div>
    </div>

    <!-- 聯絡人資訊 -->
    <div>
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-semibold text-gray-700">
          聯絡人資訊 <span class="text-red-500">*</span>
        </h3>
        <button
          type="button"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
          @click="addContact"
        >
          + 新增聯絡人
        </button>
      </div>

      <div class="space-y-4">
        <div
          v-for="(contact, index) in formData.contacts"
          :key="index"
          class="p-4 bg-gray-50 rounded-lg border border-gray-200"
        >
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-medium text-gray-700">聯絡人 {{ index + 1 }}</h4>
            <button
              v-if="formData.contacts.length > 1"
              type="button"
              class="text-red-600 hover:text-red-800 text-sm"
              @click="removeContact(index)"
            >
              刪除
            </button>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                姓名 <span class="text-red-500">*</span>
              </label>
              <input
                v-model="contact.name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                placeholder="聯絡人姓名"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                職稱 <span class="text-red-500">*</span>
              </label>
              <input
                v-model="contact.title"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                placeholder="例：品保經理"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                部門
              </label>
              <input
                v-model="contact.department"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                placeholder="例：品質保證部"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Email <span class="text-red-500">*</span>
              </label>
              <input
                v-model="contact.email"
                type="email"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                placeholder="email@example.com"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                電話 <span class="text-red-500">*</span>
              </label>
              <input
                v-model="contact.phone"
                type="tel"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                placeholder="02-1234-5678"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { BasicInfo } from '~/types/template-v2'

const props = defineProps<{
  modelValue?: Partial<BasicInfo>
}>()

const emit = defineEmits<{
  'update:modelValue': [value: Partial<BasicInfo>]
}>()

const formData = ref<Partial<BasicInfo>>({
  companyName: '',
  companyAddress: '',
  employees: {
    total: 0,
    male: 0,
    female: 0,
    foreign: 0,
  },
  facilities: [
    {
      location: '',
      address: '',
      area: '',
      type: '',
    },
  ],
  certifications: [],
  rbaOnlineMember: null,
  contacts: [
    {
      name: '',
      title: '',
      department: '',
      email: '',
      phone: '',
    },
  ],
  ...props.modelValue,
})

const newCertification = ref('')

const addFacility = () => {
  formData.value.facilities?.push({
    location: '',
    address: '',
    area: '',
    type: '',
  })
}

const removeFacility = (index: number) => {
  formData.value.facilities?.splice(index, 1)
}

const addCertification = () => {
  if (newCertification.value.trim()) {
    formData.value.certifications?.push(newCertification.value.trim())
    newCertification.value = ''
  }
}

const removeCertification = (index: number) => {
  formData.value.certifications?.splice(index, 1)
}

const addContact = () => {
  formData.value.contacts?.push({
    name: '',
    title: '',
    department: '',
    email: '',
    phone: '',
  })
}

const removeContact = (index: number) => {
  formData.value.contacts?.splice(index, 1)
}

watch(
  formData,
  (newValue) => {
    emit('update:modelValue', newValue)
  },
  { deep: true }
)

watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue) {
      formData.value = { ...formData.value, ...newValue }
    }
  },
  { deep: true }
)
</script>
